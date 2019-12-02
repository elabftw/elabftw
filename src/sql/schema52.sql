-- Schema 52
START TRANSACTION;
    ALTER TABLE `experiments` DROP FOREIGN KEY `fk_experiments_teams_id`;
    ALTER TABLE `experiments` DROP `team`;
    ALTER TABLE `experiments` ADD `canwrite` VARCHAR(255) NOT NULL DEFAULT 'user';
    ALTER TABLE `experiments` CHANGE `visibility` `canread` VARCHAR(255) NOT NULL DEFAULT 'team';
    ALTER TABLE `items` ADD `canwrite` VARCHAR(255) NOT NULL DEFAULT 'team';
    ALTER TABLE `items` CHANGE `visibility` `canread` VARCHAR(255) NOT NULL DEFAULT 'team';
    ALTER TABLE `users` CHANGE `default_vis` `default_read` VARCHAR(255) NULL DEFAULT 'team';
    ALTER TABLE `users` ADD `default_write` VARCHAR(255) NULL DEFAULT 'team';
    ALTER TABLE `users` DROP `allow_edit`;
    ALTER TABLE `users` DROP `allow_group_edit`;
    ALTER TABLE `team_events` ADD `experiment` int(10) UNSIGNED DEFAULT NULL;
    UPDATE config SET conf_value = 52 WHERE conf_name = 'schema';
COMMIT;
