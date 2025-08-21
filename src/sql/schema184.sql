-- schema 184 - convert Events start & end to datetime
ALTER TABLE `team_events`
    MODIFY COLUMN `start` DATETIME NOT NULL,
    MODIFY COLUMN `end` DATETIME DEFAULT NULL;
