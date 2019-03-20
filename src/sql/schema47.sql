-- Schema 47
START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('privacy_policy', NULL);
    UPDATE config SET conf_value = 47 WHERE conf_name = 'schema';
COMMIT;
