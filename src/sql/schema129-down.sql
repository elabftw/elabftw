-- revert schema 129
ALTER TABLE `api_keys` DROP COLUMN `last_used_at`;
UPDATE config SET conf_value = 128 WHERE conf_name = 'schema';
