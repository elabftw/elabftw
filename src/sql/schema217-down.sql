-- revert schema 217
ALTER TABLE `teams`
  DROP COLUMN `deletion_reason_enabled`,
  DROP COLUMN `deletion_reason_options`,
  DROP COLUMN `deletion_reason_categories`,
  DROP COLUMN `deletion_reason_tags`;

UPDATE config SET conf_value = 216 WHERE conf_name = 'schema';
