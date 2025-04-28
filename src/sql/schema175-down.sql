-- revert schema 175
ALTER TABLE `compounds` DROP COLUMN `is_antibiotic`;
ALTER TABLE `compounds` DROP COLUMN `is_drug`;
ALTER TABLE `compounds` DROP COLUMN `is_ed2health`;
ALTER TABLE `compounds` DROP COLUMN `is_ed2env`;
ALTER TABLE `compounds` DROP COLUMN `is_pbt`;
ALTER TABLE `compounds` DROP COLUMN `is_pmt`;
ALTER TABLE `compounds` DROP COLUMN `is_vpvb`;
ALTER TABLE `compounds` DROP COLUMN `is_vpvm`;

UPDATE config SET conf_value = 174 WHERE conf_name = 'schema';
