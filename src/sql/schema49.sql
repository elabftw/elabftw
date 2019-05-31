-- Schema 49
START TRANSACTION;
    CREATE TABLE `items_steps` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `item_id` int(10) unsigned NOT NULL,
        `body` text NOT NULL,
        `ordering` int(10) unsigned DEFAULT NULL,
        `finished` tinyint(1) NOT NULL DEFAULT '0',
        `finished_time` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `fk_items_steps_items_id` (`item_id`),
        CONSTRAINT `fk_items_steps_items_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    );
    CREATE TABLE `experiments_templates_steps` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `item_id` int(10) unsigned NOT NULL,
        `body` text NOT NULL,
        `ordering` int(10) unsigned DEFAULT NULL,
        `finished` tinyint(1) NOT NULL DEFAULT '0',
        `finished_time` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `fk_experiments_templates_steps_items_id` (`item_id`),
        CONSTRAINT `fk_experiments_templates_steps_items_id` FOREIGN KEY (`item_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    );
    CREATE TABLE `items_links` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `item_id` int(10) unsigned NOT NULL,
        `link_id` int(10) unsigned NOT NULL,
        PRIMARY KEY (`id`),
        KEY `fk_items_links_items_id` (`item_id`),
        KEY `fk_items_links_items_id2` (`link_id`),
        CONSTRAINT `fk_items_links_items_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_items_links_items_id2` FOREIGN KEY (`link_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    );
    CREATE TABLE `experiments_templates_links` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `item_id` int(10) unsigned NOT NULL,
        `link_id` int(10) unsigned NOT NULL,
        PRIMARY KEY (`id`),
        KEY `fk_experiments_templates_links_items_id` (`item_id`),
        KEY `fk_experiments_templates_links_items_id2` (`link_id`),
        CONSTRAINT `fk_experiments_templates_links_experiments_templates_id` FOREIGN KEY (`item_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_experiments_templates_links_items_id` FOREIGN KEY (`link_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    );
    UPDATE tags2entity SET item_type = 'experiments_templates' WHERE item_type = 'experiments_tpl';
    UPDATE config SET conf_value = 49 WHERE conf_name = 'schema';
COMMIT;
