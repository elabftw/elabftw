-- Prepare the database for version 3.0.0
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
    ALTER TABLE `items_revisions` CHANGE `userid` `userid` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `items_revisions` CHANGE `item_id` `item_id` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `items_comments` CHANGE `item_id` `item_id` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `items_comments` CHANGE `userid` `userid` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `tags` CHANGE `team` `team` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `tags` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
    ALTER TABLE `team_groups` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
    ALTER TABLE `tags2entity` CHANGE `item_id` `item_id` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `tags2entity` CHANGE `tag_id` `tag_id` INT(10) UNSIGNED NOT NULL;
COMMIT;
