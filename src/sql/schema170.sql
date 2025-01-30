-- schema 170 - add enforce exclusive edit mode to user control panel
ALTER TABLE `users` ADD `enforce_exclusive_edit_mode` TINYINT UNSIGNED NOT NULL DEFAULT 0;
