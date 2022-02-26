-- Schema 74
START TRANSACTION;
    DELETE FROM `config` WHERE `conf_name` = 'url';
    UPDATE config SET conf_value = 74 WHERE conf_name = 'schema';
COMMIT;
