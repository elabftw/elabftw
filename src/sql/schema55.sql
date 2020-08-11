-- Schema 55
START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_remote_user', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_firstname', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_lastname', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_email', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_teams', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('logout_url', '');
    ALTER TABLE `teams` ADD `force_canread` VARCHAR(255) NOT NULL DEFAULT 'team', ADD `force_canwrite` VARCHAR(255) NOT NULL DEFAULT 'user';
    ALTER TABLE `teams` ADD `do_force_canread` INT(1) NOT NULL DEFAULT 0, ADD `do_force_canwrite` INT(1) NOT NULL DEFAULT 0;
    UPDATE config SET conf_value = 55 WHERE conf_name = 'schema';
COMMIT;
