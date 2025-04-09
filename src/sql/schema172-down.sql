-- revert schema 172
ALTER TABLE `compounds` DROP COLUMN `is_serious_health_hazard`;
UPDATE config SET conf_value = 171 WHERE conf_name = 'schema';
