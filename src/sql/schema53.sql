START TRANSACTION;
    ALTER TABLE `users` ADD `show_team_templates` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `show_team`;
    ALTER TABLE `experiments_templates` ADD `canread` VARCHAR(255) NOT NULL DEFAULT 'team' AFTER `userid`,
        ADD `canwrite` VARCHAR(255) NOT NULL DEFAULT 'user' AFTER `canread`;
    UPDATE config SET conf_value = 53 WHERE conf_name = 'schema';
COMMIT;
