-- Schema 113
-- split tags2entity -> tags2items + tags2items_types + tags2experiments + tags2experiments_templates
-- tags2entity remove row if user doesn't exist
DELETE tags2entity
  FROM tags2entity
  LEFT JOIN tags
    ON (tags2entity.tag_id = tags.id)
  WHERE tags.id IS NULL;
-- tags2entity remove row if item does not exist
DELETE tags2entity
  FROM tags2entity
  LEFT JOIN items
    ON (tags2entity.item_id = items.id)
  WHERE tags2entity.item_type LIKE 'items' AND items.id IS NULL;
-- tags2entity remove row if items_types does not exist
DELETE tags2entity
  FROM tags2entity
  LEFT JOIN items_types
    ON (tags2entity.item_id = items_types.id)
  WHERE tags2entity.item_type LIKE 'items_types' AND items_types.id IS NULL;
-- tags2entity remove row if experiment does not exist
DELETE tags2entity
  FROM tags2entity
  LEFT JOIN experiments
    ON (tags2entity.item_id = experiments.id)
  WHERE tags2entity.item_type LIKE 'experiments' AND experiments.id IS NULL;
-- tags2entity remove row if experiments_templates does not exist
DELETE tags2entity
  FROM tags2entity
  LEFT JOIN experiments_templates
    ON (tags2entity.item_id = experiments_templates.id)
  WHERE tags2entity.item_type LIKE 'experiments_templates' AND experiments_templates.id IS NULL;

-- create tags2items
CREATE TABLE `tags2items` (
  `items_id` int UNSIGNED NOT NULL,
  `tags_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`items_id`, `tags_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
-- tags2items add FKs
ALTER TABLE `tags2items`
  ADD KEY `fk_tags2items_items_id` (`items_id`),
  ADD KEY `fk_tags2items_tags_id` (`tags_id`);
-- tags2items add constraints
ALTER TABLE `tags2items`
  ADD CONSTRAINT `fk_tags2items_items_id`
    FOREIGN KEY (`items_id`) REFERENCES `items` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tags2items_tags_id`
    FOREIGN KEY (`tags_id`) REFERENCES `tags` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;
-- tags2items copy data, avoid duplications
INSERT INTO tags2items (items_id, tags_id)
  SELECT DISTINCT item_id, tag_id
  FROM tags2entity WHERE item_type LIKE 'items';

-- create tags2items_types
CREATE TABLE `tags2items_types` (
  `items_types_id` int UNSIGNED NOT NULL,
  `tags_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`items_types_id`, `tags_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
-- tags2items_types add FKs
ALTER TABLE `tags2items_types`
  ADD KEY `fk_tags2items_types_items_types_id` (`items_types_id`),
  ADD KEY `fk_tags2items_types_tags_id` (`tags_id`);
-- tags2items_types add constraints
ALTER TABLE `tags2items_types`
  ADD CONSTRAINT `fk_tags2items_types_items_types_id`
    FOREIGN KEY (`items_types_id`) REFERENCES `items_types` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tags2items_types_tags_id`
    FOREIGN KEY (`tags_id`) REFERENCES `tags` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;
-- tags2items_types copy data, avoid duplications
INSERT INTO tags2items_types (items_types_id, tags_id)
  SELECT DISTINCT item_id, tag_id
  FROM tags2entity WHERE item_type LIKE 'items_types';

-- create tags2experiments
CREATE TABLE `tags2experiments` (
  `experiments_id` int UNSIGNED NOT NULL,
  `tags_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`experiments_id`, `tags_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
-- tags2experiments add FKs
ALTER TABLE `tags2experiments`
  ADD KEY `fk_tags2experiments_experiments_id` (`experiments_id`),
  ADD KEY `fk_tags2experiments_tags_id` (`tags_id`);
-- tags2experiments add constraints
ALTER TABLE `tags2experiments`
  ADD CONSTRAINT `fk_tags2experiments_experiments_id`
    FOREIGN KEY (`experiments_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tags2experiments_tags_id`
    FOREIGN KEY (`tags_id`) REFERENCES `tags` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;
-- tags2experiments copy data, avoid duplications
INSERT INTO tags2experiments (experiments_id, tags_id)
  SELECT DISTINCT item_id, tag_id
  FROM tags2entity WHERE item_type LIKE 'experiments';

-- create tags2experiments_templates
CREATE TABLE `tags2experiments_templates` (
  `experiments_templates_id` int UNSIGNED NOT NULL,
  `tags_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`experiments_templates_id`, `tags_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
-- tags2experiments_templates add FKs
ALTER TABLE `tags2experiments_templates`
  ADD KEY `fk_tags2experiments_templates_experiments_templates_id` (`experiments_templates_id`),
  ADD KEY `fk_tags2experiments_templates_tags_id` (`tags_id`);
-- tags2experiments_templates add constraints
ALTER TABLE `tags2experiments_templates`
  ADD CONSTRAINT `fk_tags2experiments_templates_experiments_templates_id`
    FOREIGN KEY (`experiments_templates_id`) REFERENCES `experiments_templates` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tags2experiments_templates_tags_id`
    FOREIGN KEY (`tags_id`) REFERENCES `tags` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;
-- tags2experiments_templates copy data, avoid duplications
INSERT INTO tags2experiments_templates (experiments_templates_id, tags_id)
  SELECT DISTINCT item_id, tag_id
  FROM tags2entity WHERE item_type LIKE 'experiments_templates';

-- tags2entity drop table
DROP TABLE tags2entity;
UPDATE config SET conf_value = 113 WHERE conf_name = 'schema';
