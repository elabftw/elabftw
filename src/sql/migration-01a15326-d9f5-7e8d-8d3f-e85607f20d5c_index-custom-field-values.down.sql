ALTER TABLE `experiments`
  DROP INDEX `idx_experiments_q`,
  DROP COLUMN `metadata_search`,
  ADD FULLTEXT INDEX `idx_experiments_q` (`title`, `body`, `elabid`);

ALTER TABLE `items`
  DROP INDEX `idx_items_q`,
  DROP COLUMN `metadata_search`,
  ADD FULLTEXT INDEX `idx_items_q` (`title`, `body`, `elabid`);

ALTER TABLE `experiments_templates`
  DROP INDEX `idx_experiments_templates_q`,
  DROP COLUMN `metadata_search`,
  ADD FULLTEXT INDEX `idx_experiments_templates_q` (`title`, `body`, `elabid`);

ALTER TABLE `items_types`
  DROP INDEX `idx_items_types_q`,
  DROP COLUMN `metadata_search`,
  ADD FULLTEXT INDEX `idx_items_types_q` (`title`, `body`, `elabid`);
