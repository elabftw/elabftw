-- Schema 68
START TRANSACTION;
    ALTER TABLE `items_types` CHANGE `template` `body` TEXT NULL DEFAULT NULL;
    ALTER TABLE `items_types` CHANGE `color` `color` VARCHAR(6) NOT NULL DEFAULT '29aeb9';
    CREATE TABLE `items_types_steps` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `item_id` int(10) unsigned NOT NULL,
        `body` text NOT NULL,
        `ordering` int(10) unsigned DEFAULT NULL,
        `finished` tinyint(1) NOT NULL DEFAULT '0',
        `finished_time` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `fk_items_types_steps_items_id` (`item_id`),
        CONSTRAINT `fk_items_types_steps_items_id` FOREIGN KEY (`item_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    );
    CREATE TABLE `items_types_links` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `item_id` int(10) unsigned NOT NULL,
        `link_id` int(10) unsigned NOT NULL,
        PRIMARY KEY (`id`),
        KEY `fk_items_types_links_items_id` (`item_id`),
        KEY `fk_items_types_links_items_id2` (`link_id`),
        CONSTRAINT `fk_items_types_links_items_types_id` FOREIGN KEY (`item_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_items_types_links_items_id` FOREIGN KEY (`link_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    );
    UPDATE config SET conf_value = 68 WHERE conf_name = 'schema';
COMMIT;
