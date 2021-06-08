START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('admins_create_users', 1);
    UPDATE `config` SET `conf_value` = 59 WHERE `conf_name` = 'schema';
COMMIT;
