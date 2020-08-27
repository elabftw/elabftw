-- Schema 55
START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_remote_user', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_firstname', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_lastname', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_email', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_teams', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('logout_url', '');
    ALTER TABLE `teams` ADD `force_canread` VARCHAR(255) NOT NULL DEFAULT 'team', ADD `force_canwrite` VARCHAR(255) NOT NULL DEFAULT 'user';
    ALTER TABLE `teams` ADD `do_force_canread` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0, ADD `do_force_canwrite` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
    ALTER TABLE `teams` ADD `visible` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;
    UPDATE users SET orderby = 'date' WHERE orderby IS NULL;
    UPDATE users SET sort = 'desc' WHERE sort IS NULL;
    ALTER TABLE `users` CHANGE `orderby` `orderby` VARCHAR(255) NOT NULL DEFAULT 'date';
    ALTER TABLE `users` CHANGE `sort` `sort` VARCHAR(255) NOT NULL DEFAULT 'desc';
    UPDATE config SET conf_value = 55 WHERE conf_name = 'schema';
COMMIT;
