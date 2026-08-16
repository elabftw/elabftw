-- schema 221
-- Allow teams to require a reason when a container is deleted.
ALTER TABLE `teams` ADD `capture_container_deletion_reason` TINYINT UNSIGNED NOT NULL DEFAULT 0;
