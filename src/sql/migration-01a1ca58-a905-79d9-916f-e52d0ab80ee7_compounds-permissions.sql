-- migration 01a1ca58-a905-79d9-916f-e52d0ab80ee7

-- Read and write permissions of the compounds, like the ones of the entries: a base and lists of teams, user groups and users.
-- The JSON columns have no default: they are added as nullable, filled, then made mandatory.
ALTER TABLE `compounds`
  ADD COLUMN `canread_base` TINYINT UNSIGNED NOT NULL DEFAULT 40 AFTER `state`,
  ADD COLUMN `canwrite_base` TINYINT UNSIGNED NOT NULL DEFAULT 40 AFTER `canread_base`,
  ADD COLUMN `canread` JSON NULL AFTER `canwrite_base`,
  ADD COLUMN `canwrite` JSON NULL AFTER `canread`;
-- the compounds were shared with everyone until now: keep it that way
UPDATE `compounds` SET
  `canread_base` = 40,
  `canwrite_base` = 40,
  `canread` = '{"teams": [], "teamgroups": [], "users": []}',
  `canwrite` = '{"teams": [], "teamgroups": [], "users": []}';
ALTER TABLE `compounds`
  MODIFY `canread` JSON NOT NULL,
  MODIFY `canwrite` JSON NOT NULL;
