-- revert schema 142
DELETE FROM config WHERE conf_name = 'min_password_length';
DELETE FROM config WHERE conf_name = 'password_complexity_requirement';
DELETE FROM config WHERE conf_name = 'max_password_age_days';
ALTER TABLE `users` DROP COLUMN `password_modified_at`;
UPDATE config SET conf_value = 141 WHERE conf_name = 'schema';
