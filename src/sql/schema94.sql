-- Schema 94
-- add created_at to all entities
UPDATE `experiments` SET `datetime` = CURRENT_TIMESTAMP WHERE `datetime` IS NULL;
ALTER TABLE `experiments` CHANGE `datetime` `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `items` ADD `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `experiments_comments` CHANGE `datetime` `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `experiments_comments` ADD `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `items_comments` CHANGE `datetime` `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `items_comments` ADD `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `teams` CHANGE `datetime` `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
UPDATE `experiments` SET `lastchange` = CURRENT_TIMESTAMP WHERE `lastchange` IS NULL;
ALTER TABLE `experiments` CHANGE `lastchange` `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
UPDATE `experiments_templates` SET `lastchange` = CURRENT_TIMESTAMP WHERE `lastchange` IS NULL;
ALTER TABLE `experiments_templates` CHANGE `lastchange` `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
UPDATE `items` SET `lastchange` = CURRENT_TIMESTAMP WHERE `lastchange` IS NULL;
ALTER TABLE `items` CHANGE `lastchange` `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
UPDATE `items_types` SET `lastchange` = CURRENT_TIMESTAMP WHERE `lastchange` IS NULL;
ALTER TABLE `items_types` CHANGE `lastchange` `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
-- the column did not exist before so set it to the creation date during the update
UPDATE `experiments_comments` SET `modified_at` = `created_at`;
UPDATE `items_comments` SET `modified_at` = `created_at`;
-- split pin2users into different tables
CREATE TABLE `pin_experiments2users` (
    `users_id` INT UNSIGNED NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL ,
    PRIMARY KEY (`users_id`, `entity_id`)
);
ALTER TABLE `pin_experiments2users`
    ADD KEY `fk_pin_experiments2users_userid` (`users_id`),
    ADD KEY `fk_pin_experiments2experiments_id` (`entity_id`);
ALTER TABLE `pin_experiments2users`
    ADD CONSTRAINT `fk_pin_experiments2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_pin_experiments2experiments_id` FOREIGN KEY (`entity_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
-- pin experiments templates
CREATE TABLE `pin_experiments_templates2users` (
    `users_id` INT UNSIGNED NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL ,
    PRIMARY KEY (`users_id`, `entity_id`)
);
ALTER TABLE `pin_experiments_templates2users`
    ADD KEY `fk_pin_experiments_templates2users_userid` (`users_id`),
    ADD KEY `fk_pin_experiments_templates2experiments_templates_id` (`entity_id`);
ALTER TABLE `pin_experiments_templates2users`
    ADD CONSTRAINT `fk_pin_experiments_templates2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_pin_experiments_templates2experiments_templates_id` FOREIGN KEY (`entity_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
-- pin items
CREATE TABLE `pin_items2users` (
    `users_id` INT UNSIGNED NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL ,
    PRIMARY KEY (`users_id`, `entity_id`)
);
ALTER TABLE `pin_items2users`
    ADD KEY `fk_pin_items2users_userid` (`users_id`),
    ADD KEY `fk_pin_items2items_id` (`entity_id`);
ALTER TABLE `pin_items2users`
    ADD CONSTRAINT `fk_pin_items2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_pin_items2items_id` FOREIGN KEY (`entity_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
-- reimport existing values from pin2users for experiments and items, but only the ones that correspond to something that actually exist to avoid any constraint issue
INSERT INTO `pin_experiments2users`(`users_id`, `entity_id`) SELECT `users_id`, `entity_id` FROM pin2users LEFT JOIN experiments ON `entity_id` = experiments.id WHERE type = 'experiments' AND experiments.id IS NOT NULL;
INSERT INTO `pin_items2users`(`users_id`, `entity_id`) SELECT `users_id`, `entity_id` FROM pin2users LEFT JOIN items ON `entity_id` = items.id WHERE type = 'items' AND items.id IS NOT NULL;
-- delete pin2users
DROP TABLE `pin2users`;
UPDATE config SET conf_value = 94 WHERE conf_name = 'schema';
