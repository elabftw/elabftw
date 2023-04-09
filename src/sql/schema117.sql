-- schema 117
ALTER TABLE `users2teams` ADD `groups_id` TINYINT UNSIGNED NOT NULL DEFAULT 4;
ALTER TABLE `users2teams` ADD CONSTRAINT `fk_users2teams_groups_id` FOREIGN KEY (`groups_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `users2teams` ADD `is_owner` TINYINT UNSIGNED NOT NULL DEFAULT 0;
UPDATE `users2teams` JOIN `users` ON (users.userid = users2teams.users_id) SET users2teams.groups_id = users.usergroup;
ALTER TABLE users RENAME COLUMN usergroup TO usergroup_old;
ALTER TABLE `users` ADD `is_sysadmin` TINYINT UNSIGNED NOT NULL DEFAULT 0;
UPDATE `users` SET is_sysadmin = 1 WHERE usergroup_old = 1;
-- add a default value for that column
ALTER TABLE `users` CHANGE `usergroup_old` `usergroup_old` TINYINT UNSIGNED NOT NULL DEFAULT '4';
UPDATE config SET conf_value = 117 WHERE conf_name = 'schema';
