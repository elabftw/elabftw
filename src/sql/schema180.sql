-- schema 180
ALTER TABLE `users2teams` DROP FOREIGN KEY `fk_users2teams_groups_id`;
ALTER TABLE `users2teams` DROP INDEX `fk_users2teams_groups_id`;

-- 1) Convert existing values: 2 → 1, 4 → 0
UPDATE users2teams SET groups_id = IF(groups_id = 2, 1, 0);

-- 2) Rename the column from groups_id to is_admin
ALTER TABLE users2teams CHANGE COLUMN groups_id is_admin TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE users2teams ADD COLUMN is_archived TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
UPDATE users2teams ut JOIN users u ON ut.users_id = u.userid SET ut.is_archived = 1 WHERE u.archived = 1;
-- maybe do that in next version ALTER TABLE users DROP COLUMN archived;
DROP TABLE `groups`;
