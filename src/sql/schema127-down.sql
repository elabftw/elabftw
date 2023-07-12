-- revert schema 127
ALTER TABLE `items` DROP COLUMN `is_bookable`;
ALTER TABLE `items` DROP COLUMN `canbook`;
ALTER TABLE `items` DROP COLUMN `book_max_minutes`;
ALTER TABLE `items` DROP COLUMN `book_max_slots`;
ALTER TABLE `items` DROP COLUMN `book_can_overlap`;
ALTER TABLE `items` DROP COLUMN `book_is_cancellable`;
ALTER TABLE `items` DROP COLUMN `book_cancel_minutes`;
ALTER TABLE `items_types` ADD `bookable` TINYINT UNSIGNED NOT NULL DEFAULT 0;

UPDATE config SET conf_value = 126 WHERE conf_name = 'schema';
