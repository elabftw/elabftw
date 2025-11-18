-- revert schema 192
DROP TABLE idps_certs;
ALTER TABLE idps
    ADD COLUMN `x509` text NOT NULL,
    ADD COLUMN `x509_new` text NOT NULL;
UPDATE config SET conf_value = 191 WHERE conf_name = 'schema';
