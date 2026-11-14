-- migration 01a19896-b1fa-7ddc-82f9-edb5fe637be9

-- The data step of this migration, Elabftw\Services\LegacyRevisionsMigration, runs before this file:
-- every legacy revision is in the *_body_revisions tables by now.
DROP TABLE IF EXISTS `experiments_revisions`;
DROP TABLE IF EXISTS `items_revisions`;
DROP TABLE IF EXISTS `experiments_templates_revisions`;
DROP TABLE IF EXISTS `items_types_revisions`;

-- every body is kept now, so the limits are gone
DELETE FROM `config` WHERE `conf_name` IN ('max_revisions', 'min_delta_revisions', 'min_days_revisions');
