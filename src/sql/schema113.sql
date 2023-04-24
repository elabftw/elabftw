-- Schema 113
INSERT INTO config (conf_name, conf_value) VALUES ('terms_of_service', NULL);
INSERT INTO config (conf_name, conf_value) VALUES ('a11y_statement', NULL);
ALTER TABLE users DROP COLUMN display_size;
ALTER TABLE `idps` CHANGE `active` `enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1;
UPDATE config SET conf_value = 113 WHERE conf_name = 'schema';
