-- revert schema 164
INSERT INTO config (conf_name, conf_value) values ('trust_imported_archives', '0');
UPDATE config SET conf_value = 163 WHERE conf_name = 'schema';
