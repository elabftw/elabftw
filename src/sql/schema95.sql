-- Schema 95
-- add support_url config
INSERT INTO config (conf_name, conf_value) VALUES ('support_url', 'https://github.com/elabftw/elabftw/issues');
UPDATE config SET conf_value = 95 WHERE conf_name = 'schema';
