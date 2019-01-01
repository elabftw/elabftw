-- Schema 44
START TRANSACTION;
    UPDATE items SET `locked` = 0 WHERE `locked` IS NULL;
    UPDATE config SET conf_value = 44 WHERE conf_name = 'schema';
COMMIT;
