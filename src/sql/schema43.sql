-- Schema 43
START TRANSACTION;
    INSERT INTO `config` (`conf_name`, `conf_value`) VALUES ('open_science', '0'), ('open_team', NULL);
    UPDATE config SET conf_value = 43 WHERE conf_name = 'schema';
COMMIT;
