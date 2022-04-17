-- Schema 86
-- remove admin timestamping config
DELETE FROM `config` WHERE conf_name = 'ts_share';
ALTER TABLE `teams` DROP COLUMN `ts_override`;
ALTER TABLE `teams` DROP COLUMN `ts_authority`;
ALTER TABLE `teams` DROP COLUMN `ts_login`;
ALTER TABLE `teams` DROP COLUMN `ts_password`;
ALTER TABLE `teams` DROP COLUMN `ts_url`;
ALTER TABLE `teams` DROP COLUMN `ts_cert`;
ALTER TABLE `teams` DROP COLUMN `ts_hash`;
INSERT INTO `config` (conf_name, conf_value) VALUES ('ts_limit', '0');
UPDATE config SET conf_value = 86 WHERE conf_name = 'schema';
