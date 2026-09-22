-- revert schema 225
CALL DropFK('experiments_steps', 'fk_experiments_steps_group_id');
CALL DropIdx('experiments_steps', 'idx_experiments_steps_group_id');
CALL DropColumn('experiments_steps', 'group_id');

CALL DropFK('items_steps', 'fk_items_steps_group_id');
CALL DropIdx('items_steps', 'idx_items_steps_group_id');
CALL DropColumn('items_steps', 'group_id');

CALL DropFK('experiments_templates_steps', 'fk_experiments_templates_steps_group_id');
CALL DropIdx('experiments_templates_steps', 'idx_experiments_templates_steps_group_id');
CALL DropColumn('experiments_templates_steps', 'group_id');

CALL DropFK('items_types_steps', 'fk_items_types_steps_group_id');
CALL DropIdx('items_types_steps', 'idx_items_types_steps_group_id');
CALL DropColumn('items_types_steps', 'group_id');

DROP TABLE IF EXISTS `experiments_step_groups`;
DROP TABLE IF EXISTS `items_step_groups`;
DROP TABLE IF EXISTS `experiments_templates_step_groups`;
DROP TABLE IF EXISTS `items_types_step_groups`;

UPDATE config SET conf_value = 224 WHERE conf_name = 'schema';
