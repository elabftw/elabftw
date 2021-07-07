-- Schema 60
START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('min_days_revisions', '23');
    UPDATE `config` SET `conf_value` = 60 WHERE `conf_name` = 'schema';
COMMIT;
