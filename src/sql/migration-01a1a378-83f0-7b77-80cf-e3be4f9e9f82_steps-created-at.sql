-- Track when a step was created.
-- Existing rows get the migration time: their real creation time is unknown.
ALTER TABLE `experiments_steps`
  ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `is_immutable`;
ALTER TABLE `items_steps`
  ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `is_immutable`;
ALTER TABLE `experiments_templates_steps`
  ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `is_immutable`;
ALTER TABLE `items_types_steps`
  ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `is_immutable`;
