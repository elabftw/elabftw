-- Schema 50
START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('announcement', NULL);
    INSERT INTO config (conf_name, conf_value) VALUES ('deletable_xp', 1);
    ALTER TABLE `items` ADD `lockedby` int(10) UNSIGNED DEFAULT NULL;
    ALTER TABLE `items` ADD `lockedwhen` timestamp NULL DEFAULT NULL;
    UPDATE `items` SET `locked` = NULL;
    ALTER TABLE `users` ADD `allow_group_edit` tinyint(1) NOT NULL DEFAULT '0';
    UPDATE config SET conf_value = 50 WHERE conf_name = 'schema';
COMMIT;
