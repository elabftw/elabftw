-- Schema 58
START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('min_delta_revisions', '100');
    UPDATE `config` SET `conf_value` = 58 WHERE `conf_name` = 'schema';
COMMIT;
