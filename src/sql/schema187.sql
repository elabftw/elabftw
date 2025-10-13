-- schema 187 - convert Events start & end to datetime
-- add indexes
-- add constraint for end > start

-- temporary DATETIME column to migrate safely
ALTER TABLE `team_events`
    ADD COLUMN `start_dt` DATETIME NULL AFTER `item`,
    ADD COLUMN `end_dt` DATETIME NULL AFTER `start_dt`;

-- migrate start column (handles '', zero-date, ISO 'T', and date-only)
UPDATE `team_events`
SET `start_dt` = CASE
    WHEN `start` IS NULL OR `start` = '' OR `start` IN ('0000-00-00 00:00:00','0000-00-00T00:00:00') THEN NULL
    WHEN LENGTH(REPLACE(`start`, 'T', ' ')) = 10
        THEN STR_TO_DATE(REPLACE(`start`, 'T', ' '), '%Y-%m-%d')
    ELSE STR_TO_DATE(LEFT(REPLACE(`start`, 'T', ' '), 19), '%Y-%m-%d %H:%i:%s')
END;

-- migrate end column
UPDATE `team_events`
SET `end_dt` = CASE
    WHEN `end` IS NULL OR `end` = '' OR `end` IN ('0000-00-00 00:00:00','0000-00-00T00:00:00') THEN NULL
    WHEN LENGTH(REPLACE(`end`, 'T', ' ')) = 10
        THEN STR_TO_DATE(REPLACE(`end`, 'T', ' '), '%Y-%m-%d')
    ELSE STR_TO_DATE(LEFT(REPLACE(`end`, 'T', ' '), 19), '%Y-%m-%d %H:%i:%s')
END;

-- backfill missing ends to start because we don't want null values
UPDATE `team_events`
SET `end_dt` = `start_dt`
WHERE `end_dt` IS NULL;

-- Verify before locking nullability (should return zero rows)
SELECT id FROM team_events WHERE start_dt IS NULL
UNION ALL
SELECT id FROM team_events WHERE end_dt IS NULL;

-- now enforce NOT NULL
ALTER TABLE `team_events`
    MODIFY COLUMN `start_dt` DATETIME NOT NULL,
    MODIFY COLUMN `end_dt` DATETIME NOT NULL;

-- finally, Swap columns
ALTER TABLE `team_events`
    DROP COLUMN `start`,
    DROP COLUMN `end`,
        CHANGE COLUMN `start_dt` `start` DATETIME NOT NULL,
        CHANGE COLUMN `end_dt` `end` DATETIME NOT NULL;

-- Constraints
ALTER TABLE `team_events`
    ADD CONSTRAINT `chk_end_after_start` CHECK (`end` >= `start`);

-- performance: indexes for scheduler queries
CREATE INDEX `idx_team_events_item_start_end` ON `team_events` (`item`, `start`, `end`);
CREATE INDEX `idx_team_events_team_start_end` ON `team_events` (`team`, `start`, `end`);
CREATE INDEX `idx_team_events_user_start_end` ON `team_events` (`userid`, `start`, `end`);
