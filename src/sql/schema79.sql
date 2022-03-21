-- Schema 79
-- add deadline to steps
START TRANSACTION;
    ALTER TABLE `experiments_steps` ADD `deadline` datetime NULL DEFAULT NULL;
    ALTER TABLE `experiments_templates_steps` ADD `deadline` datetime NULL DEFAULT NULL;
    ALTER TABLE `items_steps` ADD `deadline` datetime NULL DEFAULT NULL;
    ALTER TABLE `items_types_steps` ADD `deadline` datetime NULL DEFAULT NULL;
    UPDATE config SET conf_value = 79 WHERE conf_name = 'schema';
COMMIT;
