-- revert schema 140
DELETE FROM config WHERE conf_name = 'emit_audit_logs';
DELETE FROM config WHERE conf_name = 'chat_url';
DELETE FROM config WHERE conf_name = 'admins_archive_users';
ALTER TABLE `users` DROP COLUMN `last_seen_version`;
UPDATE config SET conf_value = 139 WHERE conf_name = 'schema';
