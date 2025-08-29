FROM php:8.3-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libpq-dev

# Очистка кеша
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Установка PHP расширений
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка Node.js
RUN curl -sL https://deb.nodesource.com/setup_22.x | bash -
RUN apt-get install -y nodejs

# Создание рабочей директории
WORKDIR /var/www/html

# Копирование только composer файлов сначала
COPY composer.json composer.lock* ./

# Копирование остальных файлов
COPY . .

# Установка зависимостей Composer
RUN composer install --no-dev --no-autoloader --no-scripts

# Генерация autoloader
RUN composer dump-autoload --optimize

# Установка прав
RUN chown -R www-data:www-data /var/www/html/storage
RUN chown -R www-data:www-data /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage
RUN chmod -R 775 /var/www/html/bootstrap/cache

# Если есть frontend зависимости
RUN if [ -f "package.json" ]; then npm install && npm run build; fi

EXPOSE 9000
