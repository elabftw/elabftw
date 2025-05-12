-- revert schema 174
ALTER TABLE `users` DROP COLUMN `scheduler_layout`;
UPDATE config SET conf_value = 173 WHERE conf_name = 'schema';
