-- revert schema 122
ALTER TABLE `users` DROP COLUMN `token_created_at`;
DELETE FROM config WHERE conf_name = 'cookie_validity_time';
DELETE FROM config WHERE conf_name = 'remember_me_checked';
DELETE FROM config WHERE conf_name = 'remember_me_allowed';
UPDATE config SET conf_value = 121 WHERE conf_name = 'schema';
