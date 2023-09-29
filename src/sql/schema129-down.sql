-- revert schema 129
-- combine tags2items, tags2items_types, tags2experiments, tags2experiments_templates into tags2entity
-- create tags2entity
CREATE TABLE `tags2entity` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` int UNSIGNED NOT NULL,
  `tag_id` int UNSIGNED NOT NULL,
  `item_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- copy data from tags2items
INSERT INTO `tags2entity` (`item_id`, `tag_id`, `item_type`)
  SELECT `items_id`, `tags_id`, 'items'
  FROM `tags2items`;

-- copy data from tags2items_types
INSERT INTO `tags2entity` (`item_id`, `tag_id`, `item_type`)
  SELECT `items_types_id`, `tags_id`, 'items_types'
  FROM `tags2items_types`;

-- copy data from tags2experiments
INSERT INTO `tags2entity` (`item_id`, `tag_id`, `item_type`)
  SELECT `experiments_id`, `tags_id`, 'experiments'
  FROM `tags2experiments`;

-- copy data from tags2experiments_templates
INSERT INTO `tags2entity` (`item_id`, `tag_id`, `item_type`)
  SELECT `experiments_templates_id`, `tags_id`, 'experiments_templates'
  FROM `tags2experiments_templates`;

DROP TABLE `tags2items`;
DROP TABLE `tags2items_types`;
DROP TABLE `tags2experiments`;
DROP TABLE `tags2experiments_templates`;
UPDATE config SET conf_value = 128 WHERE conf_name = 'schema';
