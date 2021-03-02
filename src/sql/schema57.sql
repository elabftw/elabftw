-- Schema 57
START TRANSACTION;
    ALTER TABLE `users` ADD `display_mode` VARCHAR(2) NOT NULL DEFAULT 'it';
    ALTER TABLE `users` CHANGE `password` `password` VARCHAR(255) NULL DEFAULT NULL;
    ALTER TABLE `users` CHANGE `salt` `salt` VARCHAR(255) NULL DEFAULT NULL;
    ALTER TABLE `users` ADD `password_hash` VARCHAR(255) NULL DEFAULT NULL AFTER `password`;
    INSERT INTO config (conf_name, conf_value) VALUES ('devmode', '0');
    INSERT INTO config (conf_name, conf_value) VALUES ('autologout_time', '0');
    INSERT INTO config (conf_name, conf_value) VALUES ('min_delta_revisions', '100');
    ALTER TABLE `items` ADD `elabid` VARCHAR(255) NOT NULL;
    ALTER TABLE `experiments` CHANGE `datetime` `datetime` TIMESTAMP NULL DEFAULT NULL;
    ALTER TABLE `experiments` ADD `metadata` JSON NULL DEFAULT NULL;
    ALTER TABLE `items` ADD `metadata` JSON NULL DEFAULT NULL;
    ALTER TABLE `experiments_templates` ADD `metadata` JSON NULL DEFAULT NULL;
    ALTER TABLE `items_types` ADD `metadata` JSON NULL DEFAULT NULL;
    ALTER TABLE `items_types` ADD `canread` VARCHAR(255) NOT NULL DEFAULT 'team';
    ALTER TABLE `items_types` ADD`canwrite` VARCHAR(255) NOT NULL DEFAULT 'team';

    -- Making item nullable in team_events, so that an event can be associated to a user.
    ALTER TABLE `team_events` CHANGE `item` `item` int(10) UNSIGNED DEFAULT NULL;
    -- An integer that indicates whether the user has selected a step to be scheduled.
    -- 0: nothing to do, 1: pending schedule, 2: scheduled
    ALTER TABLE `experiments_steps` ADD `schedule_status` int(10) UNSIGNED NOT NULL DEFAULT '0';
    ALTER TABLE `items_steps` ADD `schedule_status` int(10) UNSIGNED NOT NULL DEFAULT '0';
    ALTER TABLE `experiments_templates_steps` ADD `schedule_status` int(10) UNSIGNED NOT NULL DEFAULT '0';

    -- We can now link a step to an event (one to one)
    ALTER TABLE `team_events` ADD `experiments_step` int(10) UNSIGNED DEFAULT NULL UNIQUE,
    ADD KEY `fk_team_events_experiments_steps_id` (`experiments_step`),
    ADD CONSTRAINT `fk_team_events_experiments_steps_id` FOREIGN KEY (`experiments_step`) REFERENCES `experiments_steps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

    UPDATE `config` SET `conf_value` = 57 WHERE `conf_name` = 'schema';
COMMIT;
