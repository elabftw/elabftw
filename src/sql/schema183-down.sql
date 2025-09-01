-- revert schema 183
CALL drop_fk_if_exists('items_types', 'category');
CALL drop_fk_if_exists('items', 'category');
DROP TABLE IF EXISTS `items_categories`;
CALL DropFK('experiments_categories', 'fk_experiments_categories_teams_id');
CALL DropIdx('experiments_categories', 'fk_experiments_categories_teams_team_id');
CALL DropColumn('items_types', 'category');
CALL DropFK('items', 'fk_items_items_types_id');
CALL DropIdx('items', 'fk_items_items_types_id');

CALL DropColumn('experiments_categories', 'created_at');
CALL DropColumn('experiments_categories', 'modified_at');
CALL DropColumn('experiments_status', 'created_at');
CALL DropColumn('experiments_status', 'modified_at');
CALL DropColumn('items_status', 'created_at');
CALL DropColumn('items_status', 'modified_at');

DROP TABLE IF EXISTS `experiments_templates_status`;

ALTER TABLE teams
  ADD COLUMN `common_template` TEXT,
  ADD COLUMN `common_template_md` TEXT;

DROP TABLE IF EXISTS `items_types_comments`;
DROP TABLE IF EXISTS `pin_items_types2users`;
DROP TABLE IF EXISTS `items_types_status`;
DROP TABLE IF EXISTS `items_types_revisions`;

CALL DropColumn('users', 'scope_items_types');
CALL DropColumn('experiments_changelog', 'modified_at');
CALL DropColumn('experiments_revisions', 'modified_at');
CALL DropColumn('experiments_templates_changelog', 'modified_at');
CALL DropColumn('experiments_templates_revisions', 'modified_at');
CALL DropColumn('items_changelog', 'modified_at');
CALL DropColumn('items_revisions', 'modified_at');

UPDATE config SET conf_value = 182 WHERE conf_name = 'schema';
