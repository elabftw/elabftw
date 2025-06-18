-- revert schema 179
DROP TABLE IF EXISTS experiments_templates_comments;
ALTER TABLE `experiments_templates` DROP COLUMN `timestamped`;
ALTER TABLE `experiments_templates` DROP COLUMN `timestampedby`;
ALTER TABLE `experiments_templates` DROP COLUMN `timestamped_at`;
ALTER TABLE `items_types` DROP COLUMN `timestamped`;
ALTER TABLE `items_types` DROP COLUMN `timestampedby`;
ALTER TABLE `items_types` DROP COLUMN `timestamped_at`;
UPDATE config SET conf_value = 178 WHERE conf_name = 'schema';
