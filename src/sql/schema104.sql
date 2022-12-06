-- Schema 104
ALTER TABLE `experiments` RENAME COLUMN `lockedwhen` TO `locked_at`;
ALTER TABLE `experiments` RENAME COLUMN `timestampedwhen` TO `timestamped_at`;
ALTER TABLE `experiments_templates` RENAME COLUMN `lockedwhen` TO `locked_at`;
ALTER TABLE `itmes` RENAME COLUMN `lockedwhen` TO `locked_at`;
UPDATE config SET conf_value = 104 WHERE conf_name = 'schema';
