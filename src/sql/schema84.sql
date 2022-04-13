-- Schema 84
-- improve primary key for links
START TRANSACTION;
    ALTER TABLE `experiments_links` DROP `id`;
    ALTER TABLE `experiments_links` ADD PRIMARY KEY(`item_id`, `link_id`);
    ALTER TABLE `experiments_templates_links` DROP `id`;
    ALTER TABLE `experiments_templates_links` ADD PRIMARY KEY(`item_id`, `link_id`);
    ALTER TABLE `items_links` DROP `id`;
    ALTER TABLE `items_links` ADD PRIMARY KEY(`item_id`, `link_id`);
    ALTER TABLE `items_types_links` DROP `id`;
    ALTER TABLE `items_types_links` ADD PRIMARY KEY(`item_id`, `link_id`);
    UPDATE config SET conf_value = 84 WHERE conf_name = 'schema';
COMMIT;
