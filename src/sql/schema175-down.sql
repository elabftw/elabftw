-- revert schema 175
ALTER TABLE `team_events` DROP COLUMN `created_at`;
ALTER TABLE `team_events` DROP COLUMN `modified_at`;
UPDATE config SET conf_value = 174 WHERE conf_name = 'schema';
