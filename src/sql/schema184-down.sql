-- revert schema 184
ALTER TABLE `team_events`
    MODIFY COLUMN `start` varchar(255) NOT NULL,
    MODIFY COLUMN `end` varchar(255) DEFAULT NULL;
UPDATE config SET conf_value = 183 WHERE conf_name = 'schema';
