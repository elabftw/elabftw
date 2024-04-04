-- revert schema 147
ALTER TABLE `users` ADD `cjk_fonts` tinyint UNSIGNED NOT NULL DEFAULT 0;
UPDATE config SET conf_value = 146 WHERE conf_name = 'schema';
