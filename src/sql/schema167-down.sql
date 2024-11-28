-- revert schema 167
ALTER TABLE `users` DROP COLUMN `show_weekends`;
UPDATE config SET conf_value = 166 WHERE conf_name = 'schema';
