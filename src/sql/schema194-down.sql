-- revert schema 194
ALTER TABLE `experiments_templates` MODIFY `body` TEXT;
ALTER TABLE `items_types` MODIFY `body` TEXT;
UPDATE config SET conf_value = 193 WHERE conf_name = 'schema';
