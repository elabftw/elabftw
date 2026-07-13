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

ALTER TABLE `experiments`
  ADD INDEX `idx_experiments_state_date_id` (`state`, `date`, `id`),
  ADD INDEX `idx_experiments_state_modified_id` (`state`, `modified_at`, `id`);

ALTER TABLE `items`
  ADD INDEX `idx_items_state_date_id` (`state`, `date`, `id`),
  ADD INDEX `idx_items_state_modified_id` (`state`, `modified_at`, `id`);

ALTER TABLE `experiments_templates`
  ADD INDEX `idx_experiments_templates_state_created_id` (`state`, `created_at`, `id`),
  ADD INDEX `idx_experiments_templates_state_modified_id` (`state`, `modified_at`, `id`);

ALTER TABLE `items_types`
  ADD INDEX `idx_items_types_state_created_id` (`state`, `created_at`, `id`),
  ADD INDEX `idx_items_types_state_modified_id` (`state`, `modified_at`, `id`);

ALTER TABLE `experiments_steps`
  ADD INDEX `idx_experiments_steps_next` (`item_id`, `finished`, `ordering`, `id`);

ALTER TABLE `items_steps`
  ADD INDEX `idx_items_steps_next` (`item_id`, `finished`, `ordering`, `id`);

ALTER TABLE `experiments_templates_steps`
  ADD INDEX `idx_experiments_templates_steps_next` (`item_id`, `finished`, `ordering`, `id`);

ALTER TABLE `items_types_steps`
  ADD INDEX `idx_items_types_steps_next` (`item_id`, `finished`, `ordering`, `id`);

ALTER TABLE `experiments_comments`
  ADD INDEX `idx_experiments_comments_item_created` (`item_id`, `created_at`);

ALTER TABLE `items_comments`
  ADD INDEX `idx_items_comments_item_created` (`item_id`, `created_at`);

ALTER TABLE `experiments_templates_comments`
  ADD INDEX `idx_experiments_templates_comments_item_created` (`item_id`, `created_at`);

ALTER TABLE `items_types_comments`
  ADD INDEX `idx_items_types_comments_item_created` (`item_id`, `created_at`);

ALTER TABLE experiments
  ADD INDEX idx_experiments_user_state_modified_id
  (userid, state, modified_at, id);

ALTER TABLE experiments_templates
  ADD INDEX idx_experiments_templates_user_state_modified_id
  (userid, state, modified_at, id);

ALTER TABLE items
  ADD INDEX idx_items_user_state_modified_id
  (userid, state, modified_at, id);

ALTER TABLE items_types
  ADD INDEX idx_items_types_user_state_modified_id
  (userid, state, modified_at, id);
