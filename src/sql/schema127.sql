-- schema 127
ALTER TABLE `items` ADD `is_bookable` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items` ADD `canbook` JSON NULL DEFAULT NULL;
ALTER TABLE `items` ADD `book_max_minutes` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items` ADD `book_max_slots` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items` ADD `book_can_overlap` TINYINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `items` ADD `book_is_cancellable` TINYINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `items` ADD `book_cancel_minutes` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items_types` DROP COLUMN `bookable`;

UPDATE config SET conf_value = 127 WHERE conf_name = 'schema';
