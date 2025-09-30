-- revert schema 186
ALTER TABLE `team_events`
    MODIFY COLUMN `start` varchar(255) NOT NULL,
    MODIFY COLUMN `end` varchar(255) DEFAULT NULL;
-- Add back temporary VARCHAR columns after `item`
ALTER TABLE `team_events`
    ADD COLUMN `start_rollback` VARCHAR(255) NULL AFTER `item`,
    ADD COLUMN `end_rollback` VARCHAR(255) NULL AFTER `start_rollback`;

-- Backfill from the current DATETIME columns
UPDATE `team_events`
    SET `start_rollback` = REPLACE(DATE_FORMAT(`start`, '%Y-%m-%d %H:%i:%s'), ' ', 'T'),
        `end_rollback` = IFNULL(REPLACE(DATE_FORMAT(`end`, '%Y-%m-%d %H:%i:%s'), ' ', 'T'), '0000-00-00T00:00:00');

-- Drop the new DATETIME columns and swap in the old VARCHAR ones
ALTER TABLE `team_events`
DROP COLUMN `start`,
    DROP COLUMN `end`,
    CHANGE COLUMN `start_rollback` `start` VARCHAR(255) NOT NULL,
    CHANGE COLUMN `end_rollback`   `end`   VARCHAR(255) NULL;

UPDATE config SET conf_value = 185 WHERE conf_name = 'schema';
