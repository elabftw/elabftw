-- revert schema 197
ALTER TABLE `users` DROP COLUMN `dark_mode`;
-- TODO put 197 when hypernext gets the 197 schema from #6386
UPDATE config SET conf_value = 197 WHERE conf_name = 'schema';
