-- Schema 84
-- improve primary key for links
START TRANSACTION;
    -- experiments_links remove duplicates, triplicates, ...
    CREATE TABLE `tmp` LIKE `experiments_links`;
    -- tmp change PRIMARY KEY
    ALTER TABLE `tmp` DROP `id`;
    ALTER TABLE `tmp` ADD PRIMARY KEY(`item_id`, `link_id`);
    -- Copy data to tmp, ignore duplicates
    INSERT IGNORE INTO `tmp` SELECT `item_id`, `link_id` FROM `experiments_links`;
    -- Drop original and rename tmp
    DROP TABLE `experiments_links`;
    RENAME TABLE `tmp` TO `experiments_links`;
    -- experiments_links add back constraints
    ALTER TABLE `experiments_links`
      ADD CONSTRAINT `fk_experiments_links_experiments_id`
        FOREIGN KEY (`item_id`) REFERENCES `experiments` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
      ADD CONSTRAINT `fk_experiments_links_items_id`
        FOREIGN KEY (`link_id`) REFERENCES `items` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE;

    -- experiments_templates_links remove duplicates, triplicates, ...
    CREATE TABLE `tmp` LIKE `experiments_templates_links`;
    -- tmp change PRIMARY KEY
    ALTER TABLE `tmp` DROP `id`;
    ALTER TABLE `tmp` ADD PRIMARY KEY(`item_id`, `link_id`);
    -- Copy data to tmp, ignore duplicates
    INSERT IGNORE INTO `tmp` SELECT `item_id`, `link_id` FROM `experiments_templates_links`;
    -- Drop original and rename tmp
    DROP TABLE `experiments_templates_links`;
    RENAME TABLE `tmp` TO `experiments_templates_links`;
    -- experiments_templates_links add back constraints
    ALTER TABLE `experiments_templates_links`
      ADD CONSTRAINT `fk_experiments_templates_links_experiments_templates_id`
        FOREIGN KEY (`item_id`) REFERENCES `experiments_templates` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
      ADD CONSTRAINT `fk_experiments_templates_links_items_id`
        FOREIGN KEY (`link_id`) REFERENCES `items` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE;

    -- items_links remove duplicates, triplicates, ...
    CREATE TABLE `tmp` LIKE `items_links`;
    -- tmp change PRIMARY KEY
    ALTER TABLE `tmp` DROP `id`;
    ALTER TABLE `tmp` ADD PRIMARY KEY(`item_id`, `link_id`);
    -- Copy data to tmp, ignore duplicates
    INSERT IGNORE INTO `tmp` SELECT `item_id`, `link_id` FROM `items_links`;
    -- Drop original and rename tmp
    DROP TABLE `items_links`;
    RENAME TABLE `tmp` TO `items_links`;
    -- items_links add back constraints
    ALTER TABLE `items_links`
      ADD CONSTRAINT `fk_items_links_items_id`
        FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
      ADD CONSTRAINT `fk_items_links_items_id2`
        FOREIGN KEY (`link_id`) REFERENCES `items` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE;

    -- items_types_links remove duplicates, triplicates, ...
    CREATE TABLE `tmp` LIKE `items_types_links`;
    -- tmp change PRIMARY KEY
    ALTER TABLE `tmp` DROP `id`;
    ALTER TABLE `tmp` ADD PRIMARY KEY(`item_id`, `link_id`);
    ALTER TABLE `tmp` RENAME INDEX `fk_items_types_links_items_id2` TO `fk_items_types_links_items_types_id`; 
    -- Copy data to tmp, ignore duplicates
    INSERT IGNORE INTO `tmp` SELECT `item_id`, `link_id` FROM `items_types_links`;
    -- Drop original and rename tmp
    DROP TABLE `items_types_links`;
    RENAME TABLE `tmp` TO `items_types_links`;
    -- items_types_links add back constraints
    ALTER TABLE `items_types_links`
      ADD CONSTRAINT `fk_items_types_links_items_id`
        FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
      ADD CONSTRAINT `fk_items_types_links_items_types_id`
        FOREIGN KEY (`link_id`) REFERENCES `items_types` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE;

    UPDATE config SET conf_value = 84 WHERE conf_name = 'schema';
COMMIT;
