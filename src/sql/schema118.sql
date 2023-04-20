-- schema 118
INSERT INTO config (conf_name, conf_value) VALUES ('s3_verify_cert', '1');
UPDATE config SET conf_value = 118 WHERE conf_name = 'schema';
