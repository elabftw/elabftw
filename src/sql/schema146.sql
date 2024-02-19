-- schema 146
ALTER TABLE `items` ADD `book_users_can_in_past` TINYINT UNSIGNED NOT NULL DEFAULT 0;
UPDATE config SET conf_value = 146 WHERE conf_name = 'schema';
