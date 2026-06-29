-- ============================================================================
--  Первоначальное создание БД журнала аудита (мера ФСТЭК РСБ.3)
--
--  Выполняется ОДНОКРАТНО администратором кластера PostgreSQL (учётной
--  записью с CREATEDB/CREATEROLE — не обязательно реальным суперпользователем
--  кластера). Создаёт:
--    1. app_audit_owner   — техническая роль-владелец БД и таблиц, используется
--                           только для прогона миграций (DDL), не для работы
--                           приложения.
--    2. БД app_audit, принадлежащую app_audit_owner.
--    3. app_audit_writer  — рабочая роль приложения (права на неё выдаёт
--                           отдельно audit_grants.sql после миграции).
--
--  Порядок применения:
--    1) psql -h <host> -U <admin> -d postgres -f database/sql/audit_bootstrap.sql
--    2) В .env временно: AUDIT_DB_USERNAME=app_audit_owner / пароль ниже.
--    3) php artisan migrate --database=audit --path=database/migrations/audit
--    4) psql -h <host> -U app_audit_owner -d app_audit -f database/sql/audit_grants.sql
--    5) В .env постоянно: AUDIT_DB_USERNAME=app_audit_writer / его пароль.
-- ============================================================================

-- Замените пароли на сгенерированные значения из секрет-хранилища перед запуском.
CREATE ROLE app_audit_owner LOGIN PASSWORD '***';
CREATE ROLE app_audit_writer LOGIN PASSWORD '***';

CREATE DATABASE app_audit OWNER app_audit_owner;

-- На случай, если public-схема новой БД унаследовала владельца кластера
-- (поведение отличается между версиями PostgreSQL) — явно переназначаем.
\connect app_audit
ALTER SCHEMA public OWNER TO app_audit_owner;
