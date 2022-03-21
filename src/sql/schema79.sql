-- Schema 79
-- add deadline to steps
START TRANSACTION;
    ALTER TABLE `experiments_steps` ADD `deadline` datetime NULL DEFAULT NULL;
    ALTER TABLE `experiments_templates_steps` ADD `deadline` datetime NULL DEFAULT NULL;
    ALTER TABLE `items_steps` ADD `deadline` datetime NULL DEFAULT NULL;
    ALTER TABLE `items_types_steps` ADD `deadline` datetime NULL DEFAULT NULL;
    ALTER TABLE `experiments_steps` ADD `deadline_notif` tinyint(1) UNSIGNED NOT NULL DEFAULT 0;
    ALTER TABLE `experiments_templates_steps` ADD `deadline_notif` tinyint(1) UNSIGNED NOT NULL DEFAULT 0;
    ALTER TABLE `items_steps` ADD `deadline_notif` tinyint(1) UNSIGNED NOT NULL DEFAULT 0;
    ALTER TABLE `items_types_steps` ADD `deadline_notif` tinyint(1) UNSIGNED NOT NULL DEFAULT 0;
    ALTER TABLE `users` ADD `notif_step_deadline` tinyint(1) NOT NULL DEFAULT '1';
    ALTER TABLE `users` ADD `notif_step_deadline_email` tinyint(1) NOT NULL DEFAULT '1';
    UPDATE config SET conf_value = 79 WHERE conf_name = 'schema';
COMMIT;
