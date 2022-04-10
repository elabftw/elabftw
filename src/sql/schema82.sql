-- Schema 82
-- add FK and constraints to experiments.category
START TRANSACTION;
    ALTER TABLE `experiments`
      ADD KEY `fk_experiments_status_id` (`category`);
    ALTER TABLE `experiments`
      ADD CONSTRAINT `fk_experiments_status_id`
        FOREIGN KEY (`category`) REFERENCES `status` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE;
    UPDATE config SET conf_value = 82 WHERE conf_name = 'schema';
COMMIT;
