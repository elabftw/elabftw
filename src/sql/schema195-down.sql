-- revert schema 195
ALTER TABLE `experiments_templates` MODIFY `body` TEXT;
ALTER TABLE `items_types` MODIFY `body` TEXT;
UPDATE config SET conf_value = 194 WHERE conf_name = 'schema';
