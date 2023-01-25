-- schema 110
INSERT INTO config (conf_name, conf_value) VALUES ('ts_balance', '0');
UPDATE config SET conf_value = 110 WHERE conf_name = 'schema';
