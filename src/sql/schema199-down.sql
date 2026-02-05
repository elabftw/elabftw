-- revert schema 199
ALTER TABLE `items` DROP COLUMN `book_price_notax`;
ALTER TABLE `items` DROP COLUMN `book_price_tax`;
ALTER TABLE `items` DROP COLUMN `book_price_currency`;
UPDATE config SET conf_value = 198 WHERE conf_name = 'schema';
