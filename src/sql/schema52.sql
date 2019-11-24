-- Schema 52
START TRANSACTION;
    ALTER TABLE `experiments` DROP FOREIGN KEY `fk_experiments_teams_id`;
    ALTER TABLE `experiments` DROP `team`;
    UPDATE config SET conf_value = 52 WHERE conf_name = 'schema';
COMMIT;
