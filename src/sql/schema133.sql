-- schema 133
ALTER TABLE `experiments` ADD UNIQUE `unique_experiments_custom_id` (`category`, `custom_id`);
ALTER TABLE `experiments_templates` ADD UNIQUE `unique_experiments_templates_custom_id` (`category`, `custom_id`);
ALTER TABLE `items` ADD UNIQUE `unique_items_custom_id` (`category`, `custom_id`);
ALTER TABLE `items_types` ADD UNIQUE `unique_items_types_custom_id` (`id`, `custom_id`);
UPDATE config SET conf_value = 133 WHERE conf_name = 'schema';
