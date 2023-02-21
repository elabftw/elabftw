-- schema 111
INSERT INTO config (conf_name, conf_value) VALUES ('smtp_verify_cert', '1');
ALTER TABLE `teams` ADD `announcement` VARCHAR(255) NULL DEFAULT NULL;
UPDATE config SET conf_value = 111 WHERE conf_name = 'schema';
