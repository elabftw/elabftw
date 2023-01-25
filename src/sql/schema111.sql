-- schema 111
-- tags2entity remove row if tag does not exists
DELETE `tags2entity`
  FROM `tags2entity`
  LEFT JOIN tags
    ON (`tags2entity`.`tag_id` = `tags`.`id`)
  WHERE `tags`.`id` IS NULL;
-- tags2entity remove duplicates, triplicates, ...
CREATE TABLE `tmp` LIKE `tags2entity`;
-- tmp change PRIMARY KEY
ALTER TABLE `tmp` DROP `id`;
ALTER TABLE `tmp` ADD PRIMARY KEY (`item_id`, `tag_id`, `item_type`);
-- tags2entity add constraints
ALTER TABLE `tmp`
  ADD CONSTRAINT `fk_tags2entity_tags_id`
    FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;
-- Copy data to tmp, ignore duplicates
INSERT IGNORE INTO `tmp` SELECT `item_id`, `tag_id`, `item_type` FROM `tags2entity`;
-- Drop original and rename tmp
DROP TABLE `tags2entity`;
RENAME TABLE `tmp` TO `tags2entity`;
UPDATE config SET conf_value = 111 WHERE conf_name = 'schema';
