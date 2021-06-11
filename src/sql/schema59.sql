START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('admins_create_users', 1);
    ALTER TABLE `team_events` ADD `item_link` INT UNSIGNED NULL DEFAULT NULL AFTER `experiment`;
    UPDATE `config` SET `conf_value` = 59 WHERE `conf_name` = 'schema';
COMMIT;
