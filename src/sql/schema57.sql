-- Schema 57
START TRANSACTION;

    ALTER TABLE `users` ADD `display_mode` VARCHAR(2) NOT NULL DEFAULT 'it';
    ALTER TABLE `users` CHANGE `password` `password` VARCHAR(255) NULL DEFAULT NULL;
    ALTER TABLE `users` CHANGE `salt` `salt` VARCHAR(255) NULL DEFAULT NULL;
    ALTER TABLE `users` ADD `password_hash` VARCHAR(255) NULL DEFAULT NULL AFTER `password`;
    ALTER TABLE `users` ADD `use_isodate` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
    ALTER TABLE `users` ADD `show_public` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
    ALTER TABLE `users` ADD `uploads_layout` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1';
    ALTER TABLE `teams` ADD `user_create_tag` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1';
    ALTER TABLE `teams` ADD `deletable_item` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1';
    INSERT INTO config (conf_name, conf_value) VALUES ('autologout_time', '0');
    INSERT INTO config (conf_name, conf_value) VALUES ('min_delta_revisions', '100');
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_user_default', '1');
    INSERT INTO config (conf_name, conf_value) VALUES ('login_announcement', NULL);
    ALTER TABLE `items` ADD `elabid` VARCHAR(255) NOT NULL;
    ALTER TABLE `experiments` CHANGE `datetime` `datetime` TIMESTAMP NULL DEFAULT NULL;
    ALTER TABLE `experiments` ADD `metadata` JSON NULL DEFAULT NULL;
    ALTER TABLE `items` ADD `metadata` JSON NULL DEFAULT NULL;
    ALTER TABLE `experiments_templates` ADD `metadata` JSON NULL DEFAULT NULL;
    ALTER TABLE `items_types` ADD `metadata` JSON NULL DEFAULT NULL;
    ALTER TABLE `items_types` ADD `canread` VARCHAR(255) NOT NULL DEFAULT 'team';
    ALTER TABLE `items_types` ADD `canwrite` VARCHAR(255) NOT NULL DEFAULT 'team';
    ALTER TABLE `experiments` ADD `rating` TINYINT(10) NOT NULL DEFAULT '0';
    ALTER TABLE `items` CHANGE `rating` `rating` TINYINT(10) NOT NULL DEFAULT '0';
    ALTER TABLE `users` CHANGE `show_team` `show_team` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1';

    ALTER TABLE `experiments_templates` CHANGE `name` `title` varchar(255) NOT NULL;
    ALTER TABLE `experiments_templates` ADD `date` int(10) UNSIGNED NOT NULL DEFAULT 20210101 AFTER `title`;
    ALTER TABLE `experiments_templates`
        ADD `locked` tinyint(3) UNSIGNED DEFAULT NULL,
        ADD `lockedby` int(10) UNSIGNED DEFAULT NULL,
        ADD `lockedwhen` timestamp NULL DEFAULT NULL;

    ALTER TABLE `teams` ADD `common_template` text AFTER `name`;

    UPDATE `teams` SET `common_template` = (SELECT `body` FROM `experiments_templates` WHERE `teams`.`id` = `experiments_templates`.`team` AND `experiments_templates`.`title` = 'default' AND `experiments_templates`.`userid` = 0);

    DELETE FROM `experiments_templates` WHERE `title` = 'default' AND `userid` = 0;

    CREATE TABLE `experiments_templates_revisions` (
        `id` int(10) UNSIGNED NOT NULL,
        `item_id` int(10) UNSIGNED NOT NULL,
        `body` mediumtext NOT NULL,
        `savedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `userid` int(10) UNSIGNED NOT NULL
    ) ENGINE=InnoDB;

    ALTER TABLE `experiments_templates_revisions`
        ADD PRIMARY KEY (`id`);

    ALTER TABLE `experiments_templates_revisions`
        MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

    ALTER TABLE `experiments_templates_revisions`
        ADD CONSTRAINT `fk_experiments_templates_revisions_experiments_templates_id` FOREIGN KEY (`item_id`) REFERENCES `experiments_templates`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        ADD CONSTRAINT `fk_experiments_templates_revisions_users_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE CASCADE ON UPDATE CASCADE;
    ALTER TABLE `users2teams` ADD `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`);
    ALTER TABLE `users2team_groups` ADD `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`);
    ALTER TABLE `tags2entity` ADD `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`);

    UPDATE `config` SET `conf_value` = 57 WHERE `conf_name` = 'schema';
COMMIT;
