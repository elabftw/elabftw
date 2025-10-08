-- schema 187 - convert Events start & end to datetime
-- first migrate to a datetime column safely
ALTER TABLE `team_events`
    ADD COLUMN `start_dt` DATETIME NULL AFTER `item`,
    ADD COLUMN `end_dt` DATETIME NULL AFTER `start_dt`;

-- migrate start column
UPDATE `team_events`
SET `start_dt` =
    CASE
        WHEN `start` IS NULL OR `start` = '' OR `start` = '0000-00-00 00:00:00'
            THEN NULL
        ELSE STR_TO_DATE(LEFT(REPLACE(`start`, 'T', ' '), 19), '%Y-%m-%d %H:%i:%s')
        END;

-- migrate end column
UPDATE `team_events`
SET `end_dt` =
    CASE
        WHEN `end` IS NULL OR `end` = '' OR `end` = '0000-00-00 00:00:00'
            THEN NULL
        ELSE STR_TO_DATE(LEFT(REPLACE(`end`, 'T', ' '), 19), '%Y-%m-%d %H:%i:%s')
        END;

-- lock nullability once backfill is ok
ALTER TABLE `team_events`
    MODIFY COLUMN `start_dt` DATETIME NOT NULL,
    MODIFY COLUMN `end_dt` DATETIME NULL;

-- finally, Swap columns
ALTER TABLE `team_events`
    DROP COLUMN `start`,
    DROP COLUMN `end`,
        CHANGE COLUMN `start_dt` `start` DATETIME NOT NULL,
        CHANGE COLUMN `end_dt` `end` DATETIME NULL;
