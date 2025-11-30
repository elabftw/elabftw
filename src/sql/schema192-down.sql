-- revert schema 192
DROP TABLE idps_certs;
DROP TABLE idps_endpoints;
ALTER TABLE idps
    ADD COLUMN `x509` text NOT NULL,
    ADD COLUMN `x509_new` text NOT NULL,
    ADD COLUMN `sso_url` varchar(255) NOT NULL,
    ADD COLUMN `sso_binding` varchar(255) NOT NULL,
    ADD COLUMN `slo_url` varchar(255) NOT NULL,
    ADD COLUMN `slo_binding` varchar(255) NOT NULL;
INSERT INTO config (conf_name, conf_value) VALUES ('saml_acs_binding', NULL);
INSERT INTO config (conf_name, conf_value) VALUES ('saml_slo_binding', NULL);
UPDATE config SET conf_value = 191 WHERE conf_name = 'schema';
