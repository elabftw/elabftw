-- schema 106
-- 10: useronly
-- 20: user + admins
-- 30: my_teams
-- 40: organization
-- 50: public
-- ------------------
-- EXPERIMENTS CANREAD
-- -------------------
-- start by increasing the column size so the new value will fit
ALTER TABLE `experiments` CHANGE `canread` `canread` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canread is set to a teamgroup
UPDATE experiments SET canread = CONCAT('{"base": 10, "teams": [], "teamgroups": [', CAST(canread AS UNSIGNED), '], "users": []}') WHERE canread NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND canread NOT LIKE "{%";
-- public
UPDATE experiments SET canread = '{"base": 50, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'public';
-- organization
UPDATE experiments SET canread = '{"base": 40, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'organization';
-- team
UPDATE experiments SET canread = '{"base": 30, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'team';
-- user+admin
UPDATE experiments SET canread = '{"base": 20, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'user';
-- user
UPDATE experiments SET canread = '{"base": 10, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'useronly';
-- now make it json type
ALTER TABLE `experiments` CHANGE `canread` `canread` JSON NOT NULL;
-- ------------------
-- EXPERIMENTS canwrite
-- -------------------
-- start by increasing the column size so the new value will fit
ALTER TABLE `experiments` CHANGE `canwrite` `canwrite` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canwrite is set to a teamgroup
UPDATE experiments SET canwrite = CONCAT('{"base": 10, "teams": [], "teamgroups": [', CAST(canwrite AS UNSIGNED), '], "users": []}') WHERE canwrite NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND canwrite NOT LIKE "{%";
-- public
UPDATE experiments SET canwrite = '{"base": 50, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'public';
-- organization
UPDATE experiments SET canwrite = '{"base": 40, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'organization';
-- team
UPDATE experiments SET canwrite = '{"base": 30, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'team';
-- user+admin
UPDATE experiments SET canwrite = '{"base": 20, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'user';
-- user
UPDATE experiments SET canwrite = '{"base": 10, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'useronly';
-- now make it json type
ALTER TABLE `experiments` CHANGE `canwrite` `canwrite` JSON NOT NULL;
-- ------------------
-- ITEMS canread
-- -------------------
-- start by increasing the column size so the new value will fit
ALTER TABLE `items` CHANGE `canread` `canread` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canread is set to a teamgroup
UPDATE items SET canread = CONCAT('{"base": 10, "teams": [], "teamgroups": [', CAST(canread AS UNSIGNED), '], "users": []}') WHERE canread NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND canread NOT LIKE "{%";
-- public
UPDATE items SET canread = '{"base": 50, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'public';
-- organization
UPDATE items SET canread = '{"base": 40, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'organization';
-- team
UPDATE items SET canread = '{"base": 30, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'team';
-- user+admin
UPDATE items SET canread = '{"base": 20, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'user';
-- user
UPDATE items SET canread = '{"base": 10, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'useronly';
-- now make it json type
ALTER TABLE `items` CHANGE `canread` `canread` JSON NOT NULL;
-- ------------------
-- ITEMS canwrite
-- -------------------
-- start by increasing the column size so the new value will fit
ALTER TABLE `items` CHANGE `canwrite` `canwrite` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canwrite is set to a teamgroup
UPDATE items SET canwrite = CONCAT('{"base": 10, "teams": [], "teamgroups": [', CAST(canwrite AS UNSIGNED), '], "users": []}') WHERE canwrite NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND canwrite NOT LIKE "{%";
-- public
UPDATE items SET canwrite = '{"base": 50, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'public';
-- organization
UPDATE items SET canwrite = '{"base": 40, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'organization';
-- team
UPDATE items SET canwrite = '{"base": 30, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'team';
-- user+admin
UPDATE items SET canwrite = '{"base": 20, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'user';
-- user
UPDATE items SET canwrite = '{"base": 10, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'useronly';
-- now make it json type
ALTER TABLE `items` CHANGE `canwrite` `canwrite` JSON NOT NULL;
-- ------------------
-- USERS default_read
-- -------------------
-- do the same for default_read in users table
ALTER TABLE `users` CHANGE `default_read` `default_read` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canread is set to a teamgroup
UPDATE users SET default_read = CONCAT('{"base": 10, "teams": [], "teamgroups": [', CAST(default_read AS UNSIGNED), '], "users": []}') WHERE default_read NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND default_read NOT LIKE "{%";
-- public
UPDATE users SET default_read = '{"base": 50, "teams": [], "teamgroups": [], "users": []}' WHERE default_read = 'public';
-- organization
UPDATE users SET default_read = '{"base": 40, "teams": [], "teamgroups": [], "users": []}' WHERE default_read = 'organization';
-- team
UPDATE users SET default_read = '{"base": 30, "teams": [], "teamgroups": [], "users": []}' WHERE default_read = 'team';
-- user+admin
UPDATE users SET default_read = '{"base": 20, "teams": [], "teamgroups": [], "users": []}' WHERE default_read = 'user';
-- user
UPDATE users SET default_read = '{"base": 10, "teams": [], "teamgroups": [], "users": []}' WHERE default_read = 'useronly';
-- now make it json type
ALTER TABLE `users` CHANGE `default_read` `default_read` JSON NOT NULL;
-- --------------------
-- USERS default_write
-- -------------------
-- do the same for default_write in users table
ALTER TABLE `users` CHANGE `default_write` `default_write` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canread is set to a teamgroup
UPDATE users SET default_write = CONCAT('{"base": 10, "teams": [], "teamgroups": [', CAST(default_write AS UNSIGNED), '], "users": []}') WHERE default_write NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND default_write NOT LIKE "{%";
-- public
UPDATE users SET default_write = '{"base": 50, "teams": [], "teamgroups": [], "users": []}' WHERE default_write = 'public';
-- organization
UPDATE users SET default_write = '{"base": 40, "teams": [], "teamgroups": [], "users": []}' WHERE default_write = 'organization';
-- team
UPDATE users SET default_write = '{"base": 30, "teams": [], "teamgroups": [], "users": []}' WHERE default_write = 'team';
-- user+admin
UPDATE users SET default_write = '{"base": 20, "teams": [], "teamgroups": [], "users": []}' WHERE default_write = 'user';
-- user
UPDATE users SET default_write = '{"base": 10, "teams": [], "teamgroups": [], "users": []}' WHERE default_write = 'useronly';
-- now make it json type
ALTER TABLE `users` CHANGE `default_write` `default_write` JSON NOT NULL;
-- -------------------
-- EXPERIMENTS TEMPLATES CANREAD
-- -------------------
-- start by increasing the column size so the new value will fit
ALTER TABLE `experiments_templates` CHANGE `canread` `canread` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canread is set to a teamgroup
UPDATE experiments_templates SET canread = CONCAT('{"base": 10, "teams": [], "teamgroups": [', CAST(canread AS UNSIGNED), '], "users": []}') WHERE canread NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND canread NOT LIKE "{%";
-- public
UPDATE experiments_templates SET canread = '{"base": 50, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'public';
-- organization
UPDATE experiments_templates SET canread = '{"base": 40, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'organization';
-- team
UPDATE experiments_templates SET canread = '{"base": 30, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'team';
-- user+admin
UPDATE experiments_templates SET canread = '{"base": 20, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'user';
-- user
UPDATE experiments_templates SET canread = '{"base": 10, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'useronly';
-- now make it json type
ALTER TABLE `experiments_templates` CHANGE `canread` `canread` JSON NOT NULL;
-- -------------------
-- experiments_templates canwrite
-- -------------------
-- start by increasing the column size so the new value will fit
ALTER TABLE `experiments_templates` CHANGE `canwrite` `canwrite` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canwrite is set to a teamgroup
UPDATE experiments_templates SET canwrite = CONCAT('{"base": 10, "teams": [], "teamgroups": [', CAST(canwrite AS UNSIGNED), '], "users": []}') WHERE canwrite NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND canwrite NOT LIKE "{%";
-- public
UPDATE experiments_templates SET canwrite = '{"base": 50, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'public';
-- organization
UPDATE experiments_templates SET canwrite = '{"base": 40, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'organization';
-- team
UPDATE experiments_templates SET canwrite = '{"base": 30, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'team';
-- user+admin
UPDATE experiments_templates SET canwrite = '{"base": 20, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'user';
-- user
UPDATE experiments_templates SET canwrite = '{"base": 10, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'useronly';
-- now make it json type
ALTER TABLE `experiments_templates` CHANGE `canwrite` `canwrite` JSON NOT NULL;

-- ------------------
-- ITEMS_TYPES CANREAD
-- -------------------
-- start by increasing the column size so the new value will fit
ALTER TABLE `items_types` CHANGE `canread` `canread` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canread is set to a teamgroup
UPDATE items_types SET canread = CONCAT('{"base": 10, "teams": [], "teamgroups": [', CAST(canread AS UNSIGNED), '], "users": []}') WHERE canread NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND canread NOT LIKE "{%";
-- public
UPDATE items_types SET canread = '{"base": 50, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'public';
-- organization
UPDATE items_types SET canread = '{"base": 40, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'organization';
-- team
UPDATE items_types SET canread = '{"base": 30, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'team';
-- user+admin
UPDATE items_types SET canread = '{"base": 20, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'user';
-- user
UPDATE items_types SET canread = '{"base": 10, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'useronly';
-- now make it json type
ALTER TABLE `items_types` CHANGE `canread` `canread` JSON NOT NULL;
-- ------------------
-- ITEMS_TYPES canwrite
-- -------------------
-- start by increasing the column size so the new value will fit
ALTER TABLE `items_types` CHANGE `canwrite` `canwrite` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canwrite is set to a teamgroup
UPDATE items_types SET canwrite = CONCAT('{"base": 10, "teams": [], "teamgroups": [', CAST(canwrite AS UNSIGNED), '], "users": []}') WHERE canwrite NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND canwrite NOT LIKE "{%";
-- public
UPDATE items_types SET canwrite = '{"base": 50, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'public';
-- organization
UPDATE items_types SET canwrite = '{"base": 40, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'organization';
-- team
UPDATE items_types SET canwrite = '{"base": 30, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'team';
-- user+admin
UPDATE items_types SET canwrite = '{"base": 20, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'user';
-- user
UPDATE items_types SET canwrite = '{"base": 10, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'useronly';
-- now make it json type
ALTER TABLE `items_types` CHANGE `canwrite` `canwrite` JSON NOT NULL;

UPDATE config SET conf_value = 106 WHERE conf_name = 'schema';
