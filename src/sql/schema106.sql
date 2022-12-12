-- schema 106
-- EXPERIMENTS CANREAD
-- -------------------
-- start by increasing the column size so the new value will fit
ALTER TABLE `experiments` CHANGE `canread` `canread` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canread is set to a teamgroup
UPDATE experiments SET canread = CONCAT('{"public": false, "organization": false, "my_teams": false, "user": false, "useronly": true, "teams": [], "teamgroups": [', CAST(canread AS UNSIGNED), '], "users": []}') WHERE canread NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND canread NOT LIKE "{%";
-- public
UPDATE experiments SET canread = '{"public": true, "organization": false, "my_teams": false, "user": false, "useronly": false,"teams": [], "teamgroups": [], "users": []}' WHERE canread = 'public';
-- organization
UPDATE experiments SET canread = '{"public": false, "organization": true,  "my_teams": false, "user": false, "useronly": false,"teams": [], "teamgroups": [], "users": []}' WHERE canread = 'organization';
-- team
UPDATE experiments SET canread = '{"public": false, "organization": false, "my_teams": true, "user": false, "useronly": false, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'team';
-- user+admin
UPDATE experiments SET canread = '{"public": false, "organization": false, "my_teams": false, "user": true, "useronly": false, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'user';
-- user
UPDATE experiments SET canread = '{"public": false, "organization": false, "my_teams": false, "user": false, "useronly": true, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'useronly';
-- now make it json type
ALTER TABLE `experiments` CHANGE `canread` `canread` JSON NOT NULL;
-- EXPERIMENTS canwrite
-- -------------------
-- start by increasing the column size so the new value will fit
ALTER TABLE `experiments` CHANGE `canwrite` `canwrite` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canwrite is set to a teamgroup
UPDATE experiments SET canwrite = CONCAT('{"public": false, "organization": false, "my_teams": false, "user": false, "useronly": true, "teams": [], "teamgroups": [', CAST(canwrite AS UNSIGNED), '], "users": []}') WHERE canwrite NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND canwrite NOT LIKE "{%";
-- public
UPDATE experiments SET canwrite = '{"public": true, "organization": false, "my_teams": false, "user": false, "useronly": false,"teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'public';
-- organization
UPDATE experiments SET canwrite = '{"public": false, "organization": true,  "my_teams": false, "user": false, "useronly": false,"teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'organization';
-- team
UPDATE experiments SET canwrite = '{"public": false, "organization": false, "my_teams": true, "user": false, "useronly": false, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'team';
-- user+admin
UPDATE experiments SET canwrite = '{"public": false, "organization": false, "my_teams": false, "user": true, "useronly": false, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'user';
-- user
UPDATE experiments SET canwrite = '{"public": false, "organization": false, "my_teams": false, "user": false, "useronly": true, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'useronly';
-- now make it json type
ALTER TABLE `experiments` CHANGE `canwrite` `canwrite` JSON NOT NULL;
-- ITEMS canread
-- -------------------
-- start by increasing the column size so the new value will fit
ALTER TABLE `items` CHANGE `canread` `canread` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canread is set to a teamgroup
UPDATE items SET canread = CONCAT('{"public": false, "organization": false, "my_teams": false, "user": false, "useronly": true, "teams": [], "teamgroups": [', CAST(canread AS UNSIGNED), '], "users": []}') WHERE canread NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND canread NOT LIKE "{%";
-- public
UPDATE items SET canread = '{"public": true, "organization": false, "my_teams": false, "user": false, "useronly": false,"teams": [], "teamgroups": [], "users": []}' WHERE canread = 'public';
-- organization
UPDATE items SET canread = '{"public": false, "organization": true,  "my_teams": false, "user": false, "useronly": false,"teams": [], "teamgroups": [], "users": []}' WHERE canread = 'organization';
-- team
UPDATE items SET canread = '{"public": false, "organization": false, "my_teams": true, "user": false, "useronly": false, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'team';
-- user+admin
UPDATE items SET canread = '{"public": false, "organization": false, "my_teams": false, "user": true, "useronly": false, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'user';
-- user
UPDATE items SET canread = '{"public": false, "organization": false, "my_teams": false, "user": false, "useronly": true, "teams": [], "teamgroups": [], "users": []}' WHERE canread = 'useronly';
-- now make it json type
ALTER TABLE `items` CHANGE `canread` `canread` JSON NOT NULL;
-- ITEMS canwrite
-- -------------------
-- start by increasing the column size so the new value will fit
ALTER TABLE `items` CHANGE `canwrite` `canwrite` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canwrite is set to a teamgroup
UPDATE items SET canwrite = CONCAT('{"public": false, "organization": false, "my_teams": false, "user": false, "useronly": true, "teams": [], "teamgroups": [', CAST(canwrite AS UNSIGNED), '], "users": []}') WHERE canwrite NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND canwrite NOT LIKE "{%";
-- public
UPDATE items SET canwrite = '{"public": true, "organization": false, "my_teams": false, "user": false, "useronly": false,"teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'public';
-- organization
UPDATE items SET canwrite = '{"public": false, "organization": true,  "my_teams": false, "user": false, "useronly": false,"teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'organization';
-- team
UPDATE items SET canwrite = '{"public": false, "organization": false, "my_teams": true, "user": false, "useronly": false, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'team';
-- user+admin
UPDATE items SET canwrite = '{"public": false, "organization": false, "my_teams": false, "user": true, "useronly": false, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'user';
-- user
UPDATE items SET canwrite = '{"public": false, "organization": false, "my_teams": false, "user": false, "useronly": true, "teams": [], "teamgroups": [], "users": []}' WHERE canwrite = 'useronly';
-- now make it json type
ALTER TABLE `items` CHANGE `canwrite` `canwrite` JSON NOT NULL;
-- USERS default_canread
-- -------------------
-- do the same for default_canread in users table
ALTER TABLE `users` CHANGE `default_canread` `default_canread` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canread is set to a teamgroup
UPDATE users SET default_canread = CONCAT('{"public": false, "organization": false, "my_teams": false, "user": false, "useronly": true, "teams": [], "teamgroups": [', CAST(default_canread AS UNSIGNED), '], "users": []}') WHERE default_canread NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND default_canread NOT LIKE "{%";
-- public
UPDATE users SET default_canread = '{"public": true, "organization": false, "my_teams": false, "user": false, "useronly": false,"teams": [], "teamgroups": [], "users": []}' WHERE default_canread = 'public';
-- organization
UPDATE users SET default_canread = '{"public": false, "organization": true,  "my_teams": false, "user": false, "useronly": false,"teams": [], "teamgroups": [], "users": []}' WHERE default_canread = 'organization';
-- team
UPDATE users SET default_canread = '{"public": false, "organization": false, "my_teams": true, "user": false, "useronly": false, "teams": [], "teamgroups": [], "users": []}' WHERE default_canread = 'team';
-- user+admin
UPDATE users SET default_canread = '{"public": false, "organization": false, "my_teams": false, "user": true, "useronly": false, "teams": [], "teamgroups": [], "users": []}' WHERE default_canread = 'user';
-- user
UPDATE users SET default_canread = '{"public": false, "organization": false, "my_teams": false, "user": false, "useronly": true, "teams": [], "teamgroups": [], "users": []}' WHERE default_canread = 'useronly';
-- now make it json type
ALTER TABLE `users` CHANGE `default_canread` `default_canread` JSON NOT NULL;
-- USERS default_canwrite
-- do the same for default_canwrite in users table
ALTER TABLE `users` CHANGE `default_canwrite` `default_canwrite` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- change the rows where canread is set to a teamgroup
UPDATE users SET default_canwrite = CONCAT('{"public": false, "organization": false, "my_teams": false, "user": false, "useronly": true, "teams": [], "teamgroups": [', CAST(default_canwrite AS UNSIGNED), '], "users": []}') WHERE default_canwrite NOT IN ('public', 'organization', 'team', 'user', 'useronly') AND default_canwrite NOT LIKE "{%";
-- public
UPDATE users SET default_canwrite = '{"public": true, "organization": false, "my_teams": false, "user": false, "useronly": false,"teams": [], "teamgroups": [], "users": []}' WHERE default_canwrite = 'public';
-- organization
UPDATE users SET default_canwrite = '{"public": false, "organization": true,  "my_teams": false, "user": false, "useronly": false,"teams": [], "teamgroups": [], "users": []}' WHERE default_canwrite = 'organization';
-- team
UPDATE users SET default_canwrite = '{"public": false, "organization": false, "my_teams": true, "user": false, "useronly": false, "teams": [], "teamgroups": [], "users": []}' WHERE default_canwrite = 'team';
-- user+admin
UPDATE users SET default_canwrite = '{"public": false, "organization": false, "my_teams": false, "user": true, "useronly": false, "teams": [], "teamgroups": [], "users": []}' WHERE default_canwrite = 'user';
-- user
UPDATE users SET default_canwrite = '{"public": false, "organization": false, "my_teams": false, "user": false, "useronly": true, "teams": [], "teamgroups": [], "users": []}' WHERE default_canwrite = 'useronly';
-- now make it json type
ALTER TABLE `users` CHANGE `default_canwrite` `default_canwrite` JSON NOT NULL;

UPDATE config SET conf_value = 106 WHERE conf_name = 'schema';
