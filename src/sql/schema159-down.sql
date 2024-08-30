-- revert schema 159
ALTER TABLE `idps` DROP COLUMN `source`;
DROP TABLE `idps_sources`;
UPDATE config SET conf_value = 158 WHERE conf_name = 'schema';
