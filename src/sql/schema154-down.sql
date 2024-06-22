-- revert schema 154
ALTER TABLE `users` ADD `cjk_fonts` tinyint UNSIGNED NOT NULL DEFAULT 0;
UPDATE config SET conf_value = 153 WHERE conf_name = 'schema';
