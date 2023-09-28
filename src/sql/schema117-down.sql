-- revert schema 117
ALTER TABLE `users2teams` DROP FOREIGN KEY fk_users2teams_groups_id;
ALTER TABLE `users2teams` DROP COLUMN `groups_id`;
ALTER TABLE `users2teams` DROP COLUMN `is_owner`;
ALTER TABLE `users` RENAME COLUMN `usergroup_old` TO `usergroup`;
ALTER TABLE `users` DROP COLUMN `is_sysadmin`;
UPDATE config SET conf_value = 116 WHERE conf_name = 'schema';
