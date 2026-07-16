-- schema 217
-- Add team settings to force a reason when deleting a resource (HTA compliance)
ALTER TABLE `teams`
  ADD COLUMN `deletion_reason_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN `deletion_reason_options` TEXT NULL DEFAULT NULL,
  ADD COLUMN `deletion_reason_categories` TEXT NULL DEFAULT NULL,
  ADD COLUMN `deletion_reason_tags` TEXT NULL DEFAULT NULL;

UPDATE `teams` SET `deletion_reason_options` = '["Consent withdrawn","Retention period expired","Transferred to another establishment","Used in research","Disposed per SOP"]' WHERE `deletion_reason_options` IS NULL;
