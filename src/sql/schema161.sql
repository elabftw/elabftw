-- schema 161
CREATE TABLE `items_types2experiments` (
  `item_id` int UNSIGNED NOT NULL,
  `link_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`item_id`, `link_id`),
  KEY `fk_items_types2experiments_item_id` (`item_id`),
  KEY `fk_items_types2experiments_link_id` (`link_id`),
  CONSTRAINT `fk_items_types2experiments_item_id`
    FOREIGN KEY (`item_id`) REFERENCES `items_types` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_types2experiments_link_id`
    FOREIGN KEY (`link_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
RENAME TABLE experiments_links TO experiments2items;
RENAME TABLE experiments_templates_links TO experiments_templates2items;
RENAME TABLE items_links TO items2items;
RENAME TABLE items_types_links TO items_types2items;

UPDATE config SET conf_value = 161 WHERE conf_name = 'schema';
