-- Schema 77
-- add config option for blockchain feature
START TRANSACTION;
    INSERT INTO `config` (`conf_name`, `conf_value`) VALUES ('blox_enabled', '1'), ('blox_anon', '0');
    UPDATE config SET conf_value = 77 WHERE conf_name = 'schema';
COMMIT;
