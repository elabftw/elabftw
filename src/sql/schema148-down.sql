-- revert schema 148
DROP TABLE sig_keys;
ALTER TABLE experiments_comments DROP COLUMN immutable;
ALTER TABLE items_comments DROP COLUMN immutable;
DROP TABLE experiments_request_actions;
DROP TABLE items_request_actions;
ALTER TABLE `items` DROP COLUMN `is_procurable`;
ALTER TABLE `items` DROP COLUMN `proc_pack_qty`;
ALTER TABLE `items` DROP COLUMN `proc_price_notax`;
ALTER TABLE `items` DROP COLUMN `proc_price_tax`;
ALTER TABLE `items` DROP COLUMN `proc_currency`;
DROP TABLE `procurement_requests`;
UPDATE config SET conf_value = 147 WHERE conf_name = 'schema';
