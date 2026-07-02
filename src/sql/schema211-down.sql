-- revert schema 211
ALTER TABLE `idps` DROP COLUMN `orcid_attr`;
UPDATE config SET conf_value = 210 WHERE conf_name = 'schema';
