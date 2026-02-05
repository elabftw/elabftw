-- schema 199
ALTER TABLE `items` ADD `book_price_notax` DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00;
ALTER TABLE `items` ADD `book_price_tax` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00;
ALTER TABLE `items` ADD `book_price_currency` TINYINT UNSIGNED NOT NULL DEFAULT 0;
UPDATE config SET conf_value = 199 WHERE conf_name = 'schema';
