-- revert schema 139
ALTER TABLE `items` DROP COLUMN `timestamped`;
ALTER TABLE `items` DROP COLUMN `timestampedby`;
ALTER TABLE `items` DROP COLUMN `timestamped_at`;
UPDATE config SET conf_value = 138 WHERE conf_name = 'schema';
