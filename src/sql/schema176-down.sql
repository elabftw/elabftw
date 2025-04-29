-- revert schema 176
ALTER TABLE `team_events` DROP COLUMN `created_at`;
ALTER TABLE `team_events` DROP COLUMN `modified_at`;
UPDATE config SET conf_value = 175 WHERE conf_name = 'schema';
