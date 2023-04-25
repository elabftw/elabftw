-- revert schema 116
DELETE FROM config WHERE conf_name = 'saml_fallback_orgid';
DELETE FROM config WHERE conf_name = 'saml_sync_email_idp';
ALTER TABLE `idps` DROP COLUMN `orgid_attr`;
UPDATE config SET conf_value = 115 WHERE conf_name = 'schema';
