-- Index custom field values together with the existing plain search fields.
-- MySQL maintains the stored projection for every metadata write.
-- Strip JSON quotes and literal backslashes (word separators), then decode
-- JSON escapes so newlines, tabs and Unicode escapes have real word boundaries.
-- CHAR(34) is a double quote; CHAR(92) is a backslash, independent of SQL mode.

ALTER TABLE `experiments`
  ADD COLUMN `metadata_search` LONGTEXT GENERATED ALWAYS AS (COALESCE(JSON_UNQUOTE(CONCAT(CHAR(34),
      REPLACE(REPLACE(REPLACE(JSON_EXTRACT(`metadata`, '$.extra_fields.*.value'),
        CHAR(92, 92), ' '), CHAR(92, 34), ' '), CHAR(34), ' '),
      CHAR(34))), '')) STORED,
  DROP INDEX `idx_experiments_q`,
  ADD FULLTEXT INDEX `idx_experiments_q` (`title`, `body`, `elabid`, `metadata_search`);

ALTER TABLE `items`
  ADD COLUMN `metadata_search` LONGTEXT GENERATED ALWAYS AS (COALESCE(JSON_UNQUOTE(CONCAT(CHAR(34),
      REPLACE(REPLACE(REPLACE(JSON_EXTRACT(`metadata`, '$.extra_fields.*.value'),
        CHAR(92, 92), ' '), CHAR(92, 34), ' '), CHAR(34), ' '),
      CHAR(34))), '')) STORED,
  DROP INDEX `idx_items_q`,
  ADD FULLTEXT INDEX `idx_items_q` (`title`, `body`, `elabid`, `metadata_search`);

ALTER TABLE `experiments_templates`
  ADD COLUMN `metadata_search` LONGTEXT GENERATED ALWAYS AS (COALESCE(JSON_UNQUOTE(CONCAT(CHAR(34),
      REPLACE(REPLACE(REPLACE(JSON_EXTRACT(`metadata`, '$.extra_fields.*.value'),
        CHAR(92, 92), ' '), CHAR(92, 34), ' '), CHAR(34), ' '),
      CHAR(34))), '')) STORED,
  DROP INDEX `idx_experiments_templates_q`,
  ADD FULLTEXT INDEX `idx_experiments_templates_q` (`title`, `body`, `elabid`, `metadata_search`);

ALTER TABLE `items_types`
  ADD COLUMN `metadata_search` LONGTEXT GENERATED ALWAYS AS (COALESCE(JSON_UNQUOTE(CONCAT(CHAR(34),
      REPLACE(REPLACE(REPLACE(JSON_EXTRACT(`metadata`, '$.extra_fields.*.value'),
        CHAR(92, 92), ' '), CHAR(92, 34), ' '), CHAR(34), ' '),
      CHAR(34))), '')) STORED,
  DROP INDEX `idx_items_types_q`,
  ADD FULLTEXT INDEX `idx_items_types_q` (`title`, `body`, `elabid`, `metadata_search`);
