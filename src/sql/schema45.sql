-- Schema 45
START TRANSACTION;
    -- Add active column to idps
    ALTER TABLE `idps` ADD `active` TINYINT(1) NOT NULL DEFAULT 0;
    -- Rename status (exp) and type (items) to category
    ALTER TABLE `experiments` CHANGE `status` `category` INT(255) UNSIGNED NOT NULL;
    ALTER TABLE `items` CHANGE `type` `category` INT(255) UNSIGNED NOT NULL;
    -- Delete unused tables
    DROP TABLE IF EXISTS experiments_tags;
    DROP TABLE IF EXISTS experiments_tpl_tags;
    DROP TABLE IF EXISTS items_tags;
    DROP TABLE IF EXISTS logs;
    -- Rename some columns for consistency
    ALTER TABLE `teams` CHANGE `team_id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
    ALTER TABLE `teams` CHANGE `team_name` `name` VARCHAR(255) NOT NULL;
    ALTER TABLE `teams` CHANGE `team_orgid` `orgid` VARCHAR(255) DEFAULT NULL;
    ALTER TABLE `groups` CHANGE `group_id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
    ALTER TABLE `groups` CHANGE `group_name` `name` VARCHAR(255) NOT NULL;
    -- Adjust some columns before adding the FK
    ALTER TABLE `experiments_comments` CHANGE `userid` `userid` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `experiments_comments` CHANGE `item_id` `item_id` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `experiments_links` CHANGE `link_id` `link_id` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `experiments_revisions` CHANGE `userid` `userid` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `experiments_revisions` CHANGE `item_id` `item_id` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `items_comments` CHANGE `item_id` `item_id` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `items_comments` CHANGE `userid` `userid` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `tags` CHANGE `team` `team` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `tags` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
    ALTER TABLE `team_groups` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
    ALTER TABLE `tags2entity` CHANGE `item_id` `item_id` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `tags2entity` CHANGE `tag_id` `tag_id` INT(10) UNSIGNED NOT NULL;
COMMIT;

START TRANSACTION;
    -- Add FK constraints
    ALTER TABLE `experiments_templates` ADD CONSTRAINT `fk_experiments_templates_teams_id` FOREIGN KEY (`team`) REFERENCES `teams`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `experiments` ADD CONSTRAINT `fk_experiments_teams_id` FOREIGN KEY (`team`) REFERENCES `teams`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `experiments` ADD CONSTRAINT `fk_experiments_users_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `experiments_comments` ADD CONSTRAINT `fk_experiments_comments_experiments_id` FOREIGN KEY (`item_id`) REFERENCES `experiments`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `experiments_comments` ADD CONSTRAINT `fk_experiments_comments_users_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `experiments_links` ADD CONSTRAINT `fk_experiments_links_experiments_id` FOREIGN KEY (`item_id`) REFERENCES `experiments`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `experiments_links` ADD CONSTRAINT `fk_experiments_links_items_id` FOREIGN KEY (`link_id`) REFERENCES `items`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `experiments_revisions` ADD CONSTRAINT `fk_experiments_revisions_experiments_id` FOREIGN KEY (`item_id`) REFERENCES `experiments`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `experiments_revisions` ADD CONSTRAINT `fk_experiments_revisions_users_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `experiments_steps` ADD CONSTRAINT `fk_experiments_steps_experiments_id` FOREIGN KEY (`item_id`) REFERENCES `experiments`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `items` ADD CONSTRAINT `fk_items_teams_id` FOREIGN KEY (`team`) REFERENCES `teams`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `items_comments` ADD CONSTRAINT `fk_items_comments_items_id` FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `items_comments` ADD CONSTRAINT `fk_items_comments_users_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `items_types` ADD CONSTRAINT `fk_items_types_teams_id` FOREIGN KEY (`team`) REFERENCES `teams`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `status` ADD CONSTRAINT `fk_status_teams_id` FOREIGN KEY (`team`) REFERENCES `teams`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `tags` ADD CONSTRAINT `fk_tags_teams_id` FOREIGN KEY (`team`) REFERENCES `teams`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `tags2entity` ADD CONSTRAINT `fk_tags2entity_tags_id` FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `team_events` ADD CONSTRAINT `fk_team_events_teams_id` FOREIGN KEY (`team`) REFERENCES `teams`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `team_events` ADD CONSTRAINT `fk_team_events_users_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `team_groups` ADD CONSTRAINT `fk_team_groups_teams_id` FOREIGN KEY (`team`) REFERENCES `teams`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `todolist` ADD CONSTRAINT `fk_todolist_users_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `users` ADD CONSTRAINT `fk_users_teams_id` FOREIGN KEY (`team`) REFERENCES `teams`(`id`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `users2team_groups` ADD CONSTRAINT `fk_users2team_groups_users_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `users2team_groups` ADD CONSTRAINT `fk_users2team_groups_team_groups_id` FOREIGN KEY (`groupid`) REFERENCES `team_groups`(`id`) ON DELETE cascade ON UPDATE cascade;
    UPDATE config SET conf_value = 45 WHERE conf_name = 'schema';
COMMIT;
