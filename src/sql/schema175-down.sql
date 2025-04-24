-- revert schema 175
ALTER TABLE `team_events` DROP COLUMN `created_at`;
ALTER TABLE `team_events` DROP COLUMN `modified_at`;
ALTER TABLE `users` DROP COLUMN `scope_events`;
UPDATE config SET conf_value = 174 WHERE conf_name = 'schema';
