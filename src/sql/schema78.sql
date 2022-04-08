-- Schema 77
-- Allow rollover certificates for SP and IdPs
START TRANSACTION;
    ALTER TABLE `idps` ADD `x509_new` text NOT NULL AFTER `x509`;
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_x509_new', NULL);
    UPDATE config SET conf_value = 78 WHERE conf_name = 'schema';
COMMIT;
