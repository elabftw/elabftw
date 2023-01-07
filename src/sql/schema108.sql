-- schema 108
-- ------------------
-- TEAMS FORCE_CANREAD
-- -------------------
-- start by increasing the column size so the new value will fit
ALTER TABLE `teams` CHANGE `force_canread` `force_canread` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'team';
-- public
UPDATE teams SET force_canread = '{"base": 50, "teams": [], "teamgroups": [], "users": []}' WHERE force_canread = 'public';
-- organization
UPDATE teams SET force_canread = '{"base": 40, "teams": [], "teamgroups": [], "users": []}' WHERE force_canread = 'organization';
-- team
UPDATE teams SET force_canread = '{"base": 30, "teams": [], "teamgroups": [], "users": []}' WHERE force_canread = 'team';
-- user+admin
UPDATE teams SET force_canread = '{"base": 20, "teams": [], "teamgroups": [], "users": []}' WHERE force_canread = 'user';
-- user
UPDATE teams SET force_canread = '{"base": 10, "teams": [], "teamgroups": [], "users": []}' WHERE force_canread = 'useronly';
-- now make it json type
ALTER TABLE `teams` CHANGE `force_canread` `force_canread` JSON NOT NULL;
-- ------------------
-- TEAMS FORCE_CANWRITE
-- -------------------
-- start by increasing the column size so the new value will fit
ALTER TABLE `teams` CHANGE `force_canwrite` `force_canwrite` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'user';
-- public
UPDATE teams SET force_canwrite = '{"base": 50, "teams": [], "teamgroups": [], "users": []}' WHERE force_canwrite = 'public';
-- organization
UPDATE teams SET force_canwrite = '{"base": 40, "teams": [], "teamgroups": [], "users": []}' WHERE force_canwrite = 'organization';
-- team
UPDATE teams SET force_canwrite = '{"base": 30, "teams": [], "teamgroups": [], "users": []}' WHERE force_canwrite = 'team';
-- user+admin
UPDATE teams SET force_canwrite = '{"base": 20, "teams": [], "teamgroups": [], "users": []}' WHERE force_canwrite = 'user';
-- user
UPDATE teams SET force_canwrite = '{"base": 10, "teams": [], "teamgroups": [], "users": []}' WHERE force_canwrite = 'useronly';
-- now make it json type
ALTER TABLE `teams` CHANGE `force_canwrite` `force_canwrite` JSON NOT NULL;

UPDATE config SET conf_value = 108 WHERE conf_name = 'schema';
