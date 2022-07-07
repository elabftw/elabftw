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
UPDATE config SET conf_value = 94 WHERE conf_name = 'schema';
