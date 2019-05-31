-- Schema 50
START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('announcement', NULL);
    INSERT INTO config (conf_name, conf_value) VALUES ('deletable_xp', 1);
    UPDATE config SET conf_value = 50 WHERE conf_name = 'schema';
COMMIT;
