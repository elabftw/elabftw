-- Schema 42
START TRANSACTION;
    ALTER TABLE `items` ADD `visibility` VARCHAR(255) NOT NULL DEFAULT 'team';
    UPDATE config SET conf_value = 42 WHERE conf_name = 'schema';
COMMIT;
