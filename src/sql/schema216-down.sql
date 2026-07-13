-- revert schema 216
CALL DropIdx('experiments', 'idx_experiments_q');
CALL DropIdx('items', 'idx_items_q');
CALL DropIdx('experiments_templates', 'idx_experiments_templates_q');
CALL DropIdx('items_types', 'idx_items_types_q');
CALL DropIdx('compounds', 'idx_compounds_q');
CALL DropIdx('experiments', 'idx_experiments_state_date_id');
CALL DropIdx('experiments', 'idx_experiments_state_modified_id');
CALL DropIdx('items', 'idx_items_state_date_id');
CALL DropIdx('items', 'idx_items_state_modified_id');
CALL DropIdx('experiments_templates', 'idx_experiments_templates_state_created_id');
CALL DropIdx('experiments_templates', 'idx_experiments_templates_state_modified_id');
CALL DropIdx('items_types', 'idx_items_types_state_created_id');
CALL DropIdx('items_types', 'idx_items_types_state_modified_id');
CALL DropIdx('experiments_steps', 'idx_experiments_steps_next');
CALL DropIdx('items_steps', 'idx_items_steps_next');
CALL DropIdx('experiments_templates_steps', 'idx_experiments_templates_steps_next');
CALL DropIdx('items_types_steps', 'idx_items_types_steps_next');
CALL DropIdx('experiments_comments', 'idx_experiments_comments_item_created');
CALL DropIdx('items_comments', 'idx_items_comments_item_created');
CALL DropIdx('experiments_templates_comments', 'idx_experiments_templates_comments_item_created');
CALL DropIdx('items_types_comments', 'idx_items_types_comments_item_created');

UPDATE config SET conf_value = 215 WHERE conf_name = 'schema';
