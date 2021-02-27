-- Schema 58
START TRANSACTION;
    ALTER TABLE `experiments_templates` CHANGE `name` `title` varchar(255) NOT NULL;
    ALTER TABLE `experiments_templates` ADD `date` int(10) UNSIGNED NOT NULL DEFAULT 20210101 AFTER `title`;
    ALTER TABLE `experiments_templates`
        ADD `locked` tinyint(3) UNSIGNED DEFAULT NULL,
        ADD `lockedby` int(10) UNSIGNED DEFAULT NULL,
        ADD `lockedwhen` timestamp NULL DEFAULT NULL
    AFTER `userid`;

    ALTER TABLE `teams` ADD `common_template` text AFTER `name`;

    UPDATE `teams` AS `m` SET `common_template` = (SELECT `body` FROM `experiments_templates` AS `a` WHERE `m`.`id` = `a`.`team` AND `a`.`title` = 'default' AND `a`.`userid` = 0);

    DELETE FROM `experiments_templates` WHERE `title` = 'default' AND `userid` = 0;

    CREATE TABLE `experiments_templates_revisions` (
        `id` int(10) UNSIGNED NOT NULL,
        `item_id` int(10) UNSIGNED NOT NULL,
        `body` mediumtext NOT NULL,
        `savedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `userid` int(10) UNSIGNED NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ALTER TABLE `experiments_templates_revisions`
        ADD PRIMARY KEY (`id`);

    ALTER TABLE `experiments_templates_revisions`
        MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

    ALTER TABLE `experiments_templates_revisions`
        ADD CONSTRAINT `fk_experiments_templates_revisions_experiments_templates_id` FOREIGN KEY (`item_id`) REFERENCES `experiments_templates`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        ADD CONSTRAINT `fk_experiments_templates_revisions_users_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

    UPDATE `config` SET `conf_value` = 58 WHERE `conf_name` = 'schema';
COMMIT;
