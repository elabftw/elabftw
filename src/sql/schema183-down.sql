-- revert schema 183
CALL drop_fk_if_exists('items_types', 'category');
CALL drop_fk_if_exists('items', 'category');
DROP TABLE IF EXISTS `items_categories`;
CALL DropFK('experiments_categories', 'fk_experiments_categories_teams_id');
CALL DropIdx('experiments_categories', 'fk_experiments_categories_teams_team_id');
ALTER TABLE `items_types` DROP COLUMN `category`;
CALL DropFK('items', 'fk_items_items_types_id');
CALL DropIdx('items', 'fk_items_items_types_id');

-- add missing created_at and modified_at on experiments_categories
ALTER TABLE `experiments_categories`
  DROP COLUMN `created_at`,
  DROP COLUMN `modified_at`;

-- give status to experiments templates too
DROP TABLE IF EXISTS `experiments_templates_status`;

ALTER TABLE teams
  ADD COLUMN `common_template` TEXT,
  ADD COLUMN `common_template_md` TEXT;

DROP TABLE IF EXISTS `items_types_comments`;

UPDATE config SET conf_value = 182 WHERE conf_name = 'schema';
