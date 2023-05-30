-- revert schema 122
ALTER TABLE `users` DROP COLUMN `token_created_at`;
DELETE FROM config WHERE conf_name = 'cookie_validity_time';
UPDATE config SET conf_value = 121 WHERE conf_name = 'schema';
