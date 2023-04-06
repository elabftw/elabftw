-- schema 116
INSERT INTO config (conf_name, conf_value) VALUES ('saml_fallback_orgid', '0');
INSERT INTO config (conf_name, conf_value) VALUES ('saml_sync_email_idp', '0');
ALTER TABLE `idps` ADD `orgid_attr` VARCHAR(255) NULL DEFAULT NULL;
UPDATE config SET conf_value = 116 WHERE conf_name = 'schema';
