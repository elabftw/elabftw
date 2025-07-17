-- schema 180
CALL DropFK('users2teams', 'fk_users2teams_groups_id');
CALL DropIdx('users2teams', 'fk_users2teams_groups_id');

-- 1) Convert existing values: 2 → 1, 4 → 0
UPDATE users2teams SET groups_id = IF(groups_id = 2, 1, 0);

-- 2) Rename the column from groups_id to is_admin
ALTER TABLE users2teams CHANGE COLUMN groups_id is_admin TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE users2teams ADD COLUMN is_archived TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
UPDATE users2teams ut JOIN users u ON ut.users_id = u.userid SET ut.is_archived = 1 WHERE u.archived = 1;
-- this FK might still exist in older instances
CALL DropFK('users', 'fk_users_groups_id');
CALL DropColumn('users', 'usergroup_old');
-- maybe do that in next version ALTER TABLE users DROP COLUMN if exists archived;
DROP TABLE `groups`;
-- add team settings for status/categories
ALTER TABLE teams ADD COLUMN users_canwrite_experiments_categories TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE teams ADD COLUMN users_canwrite_experiments_status TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE teams ADD COLUMN users_canwrite_resources_categories TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE teams ADD COLUMN users_canwrite_resources_status TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;
-- also remove that old column
CALL DropColumn('items_types', 'bookable_old');
DELETE FROM config WHERE conf_name = 'debug';
ALTER TABLE users ADD COLUMN can_manage_users2teams TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
