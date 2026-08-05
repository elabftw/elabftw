-- revert schema 218
ALTER TABLE `users` ADD `enforce_exclusive_edit_mode` TINYINT UNSIGNED NOT NULL DEFAULT 0;
CALL DropColumn('users', 'primary_bg');
CALL DropColumn('users', 'primary_fg');
UPDATE config SET conf_value = 217 WHERE conf_name = 'schema';
