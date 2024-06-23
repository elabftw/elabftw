-- schema 154 add user option always show owned
ALTER TABLE `users` ADD `always_show_owned` TINYINT UNSIGNED NOT NULL DEFAULT 0;
UPDATE config SET conf_value = 154 WHERE conf_name = 'schema';
