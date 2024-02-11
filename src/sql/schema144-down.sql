-- revert schema 144
ALTER TABLE `experiments` DROP COLUMN `team`;
UPDATE config SET conf_value = 143 WHERE conf_name = 'schema';
