-- revert schema 154
ALTER TABLE `users` DROP `always_show_owned`;
UPDATE config SET conf_value = 153 WHERE conf_name = 'schema';
