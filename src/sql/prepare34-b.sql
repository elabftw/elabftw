-- Prepare the database for version 3.4.0
-- this should be allowed to fail
ALTER TABLE `experiments` DROP FOREIGN KEY `fk_experiments_teams_id`;
