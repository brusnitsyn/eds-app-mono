-- ============================================================================
--  Права доступа к БД журнала аудита (мера ФСТЭК РСБ.3)
--  Учётная запись приложения может ТОЛЬКО добавлять и читать записи журнала,
--  но не изменять и не удалять их — это обеспечивает неизменяемость журнала.
--
--  Роль app_audit_owner, БД app_audit и роль app_audit_writer создаются
--  заранее скриптом database/sql/audit_bootstrap.sql. Таблица audit_log
--  создаётся миграцией (php artisan migrate --database=audit
--  --path=database/migrations/audit) ПЕРЕД запуском этого файла.
--
--  Применять под ролью-владельцем (app_audit_owner), не суперпользователем
--  кластера. PostgreSQL. Имя таблицы соответствует config('audit.table')
--  (по умолчанию audit_log).
-- ============================================================================

-- 2. Подключение и схема.
GRANT CONNECT ON DATABASE app_audit TO app_audit_writer;
GRANT USAGE ON SCHEMA public TO app_audit_writer;

-- 3. Только добавление и чтение записей журнала (никаких UPDATE/DELETE/TRUNCATE).
GRANT INSERT, SELECT ON TABLE audit_log TO app_audit_writer;
GRANT USAGE, SELECT ON SEQUENCE audit_log_id_seq TO app_audit_writer;

-- 4. Явный запрет изменения и удаления (на случай наследуемых прав).
REVOKE UPDATE, DELETE, TRUNCATE ON TABLE audit_log FROM app_audit_writer;

-- 5. Очистку по сроку хранения (команда audit:purge, мера РСБ.8) выполняет
--    ОТДЕЛЬНАЯ привилегированная роль по регламенту, не роль приложения:
--    CREATE ROLE audit_retention LOGIN PASSWORD '***';
--    GRANT DELETE ON TABLE audit_log TO audit_retention;
--
--    В этом случае запускайте artisan audit:purge под соединением с этой ролью.

-- 6. Рекомендация (РСБ.3): включить аудит самой СУБД и физически выносить
--    журнал на отдельный сервер/хранилище с репликацией в SIEM.
