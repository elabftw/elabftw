-- Schema 82
-- add missing FK and constraints
START TRANSACTION;
    ALTER TABLE `experiments`
      ADD KEY `fk_experiments_status_id` (`category`);
    ALTER TABLE `experiments`
      ADD CONSTRAINT `fk_experiments_status_id`
        FOREIGN KEY (`category`) REFERENCES `status` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE;
    ALTER TABLE `items`
      ADD KEY `fk_items_items_types_id` (`category`);
    ALTER TABLE `items`
      ADD CONSTRAINT `fk_items_items_types_id`
        FOREIGN KEY (`category`) REFERENCES `items_types` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE;
    UPDATE config SET conf_value = 82 WHERE conf_name = 'schema';
COMMIT;
