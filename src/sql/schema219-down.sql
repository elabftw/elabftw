-- revert schema 219
ALTER TABLE `teams` DROP `capture_container_deletion_reason`;

UPDATE config SET conf_value = 218 WHERE conf_name = 'schema';
