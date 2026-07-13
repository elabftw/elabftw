-- schema 216
-- Add fulltext indexes used by the plain q search fast path.
ALTER TABLE `experiments`
  ADD FULLTEXT INDEX `idx_experiments_q` (`title`, `body`, `elabid`);

ALTER TABLE `items`
  ADD FULLTEXT INDEX `idx_items_q` (`title`, `body`, `elabid`);

ALTER TABLE `experiments_templates`
  ADD FULLTEXT INDEX `idx_experiments_templates_q` (`title`, `body`, `elabid`);

ALTER TABLE `items_types`
  ADD FULLTEXT INDEX `idx_items_types_q` (`title`, `body`, `elabid`);

ALTER TABLE `compounds`
  ADD FULLTEXT INDEX `idx_compounds_q` (
    `cas_number`,
    `ec_number`,
    `name`,
    `iupac_name`,
    `inchi_key`,
    `molecular_formula`
  );
