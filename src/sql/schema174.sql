-- schema 174 - allow different scheduler default layout
ALTER TABLE `users` ADD `scheduler_layout` TINYINT UNSIGNED NOT NULL DEFAULT 0;
