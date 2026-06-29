# Этап 1: Сборка PHP зависимостей
FROM webdevops/php:8.3-alpine AS php-build

ENV WEB_DOCUMENT_ROOT=/var/www/html/public
ENV PHP_DATE_TIMEZONE=UTC
ENV PHP_DISPLAY_ERRORS=0
ENV PHP_MEMORY_LIMIT=512M
ENV PHP_MAX_EXECUTION_TIME=300
ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_MEMORY_CONSUMPTION=256

# Установка системных зависимостей + Microsoft ODBC + sqlsrv
RUN apk update && apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    oniguruma-dev \
    unixodbc-dev \
    autoconf \
    make \
    g++ \
    curl \
    gnupg \
    && case $(uname -m) in \
        x86_64) architecture="amd64" ;; \
        arm64) architecture="arm64" ;; \
        *) echo "Unsupported architecture"; exit 1 ;; \
    esac \
    && curl -O https://download.microsoft.com/download/0b3d5518-b4a7-4a2b-afc7-7ee9e967f93c/msodbcsql18_18.6.2.1-1_${architecture}.apk \
    && ACCEPT_EULA=Y apk add --allow-untrusted msodbcsql18_18.6.2.1-1_${architecture}.apk \
    && rm msodbcsql18_18.6.2.1-1_${architecture}.apk \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && apk del autoconf make g++ \
    && docker-run-bootstrap

# Чистим pecl мусор
RUN rm -rf /usr/local/lib/php/test \
    && rm -rf /usr/local/lib/php/doc \
    && rm -rf /tmp/pear \
    && rm -rf /usr/src/php \
    && rm -f /usr/src/php.tar.xz 2>/dev/null || true

# Фикс OpenSSL 3 совместимости с SQL Server 2016
COPY docker/openssl.cnf /etc/ssl/openssl.cnf

# Установка Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --apcu-autoloader \
    --no-dev

COPY . .

RUN composer dump-autoload --optimize

RUN chown -R application:application /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Этап 2: Сборка фронтенда
FROM node:20-alpine AS node-build

WORKDIR /var/www/html

COPY package.json package-lock.json* ./

RUN npm ci \
    --no-audit \
    --progress=false

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources/ ./resources/

RUN npm run build

# Этап 3: Финальный production образ
FROM webdevops/php-nginx:8.3-alpine

ENV WEB_DOCUMENT_ROOT=/var/www/html/public
ENV PHP_DATE_TIMEZONE=UTC
ENV PHP_DISPLAY_ERRORS=0
ENV PHP_MEMORY_LIMIT=512M
ENV PHP_MAX_EXECUTION_TIME=300
ENV PHP_POST_MAX_SIZE=100M
ENV PHP_UPLOAD_MAX_FILESIZE=100M
ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_MEMORY_CONSUMPTION=256
ENV PHP_OPCACHE_MAX_ACCELERATED_FILES=32531
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
ENV NGINX_WORKER_PROCESSES=auto
ENV NGINX_WORKER_CONNECTIONS=1024

# Установка системных зависимостей + Microsoft ODBC + sqlsrv
RUN apk update && apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    oniguruma-dev \
    unixodbc-dev \
    autoconf \
    make \
    g++ \
    curl \
    gnupg \
    python3 \
    py3-openssl \
    && case $(uname -m) in \
        x86_64) architecture="amd64" ;; \
        arm64) architecture="arm64" ;; \
        *) echo "Unsupported architecture"; exit 1 ;; \
    esac \
    && echo "Downloading ODBC Driver for architecture: ${architecture}" \
    && curl -fkSLo msodbcsql.apk "https://download.microsoft.com/download/0b3d5518-b4a7-4a2b-afc7-7ee9e967f93c/msodbcsql18_18.6.2.1-1_${architecture}.apk" \
    && curl -fkSLo msodbcsql.sig "https://download.microsoft.com/download/0b3d5518-b4a7-4a2b-afc7-7ee9e967f93c/msodbcsql18_18.6.2.1-1_${architecture}.sig" \
    && curl https://packages.microsoft.com/keys/microsoft.asc | gpg --import - \
    && gpg --verify msodbcsql.sig msodbcsql.apk \
    && ACCEPT_EULA=Y apk add --allow-untrusted msodbcsql.apk \
    && rm msodbcsql.apk msodbcsql.sig \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && apk del autoconf make g++ \
    && docker-run-bootstrap

# Чистим pecl мусор
RUN rm -rf /usr/local/lib/php/test \
    && rm -rf /usr/local/lib/php/doc \
    && rm -rf /tmp/pear \
    && rm -rf /usr/src/php \
    && rm -f /usr/src/php.tar.xz 2>/dev/null || true

# Фикс OpenSSL 3 совместимости с SQL Server 2016
COPY docker/openssl.cnf /etc/ssl/openssl.cnf

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/app.conf /etc/nginx/conf.d/default.conf
RUN rm -f /etc/nginx/conf.d/10-docker.conf
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
RUN mkdir -p /var/log/supervisor

WORKDIR /var/www/html

COPY --chown=application:application --from=php-build /var/www/html .
COPY --chown=application:application --from=node-build /var/www/html/public/build ./public/build

RUN chown -R application:application /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

RUN php artisan storage:link

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -kf https://localhost/up || exit 1

EXPOSE 80

CMD ["/bin/sh", "-c", "mkdir -p /var/log/supervisor /var/log/nginx && exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf"]
