-- revert schema 216
CALL DropIdx('experiments', 'idx_experiments_q');
CALL DropIdx('items', 'idx_items_q');
CALL DropIdx('experiments_templates', 'idx_experiments_templates_q');
CALL DropIdx('items_types', 'idx_items_types_q');
CALL DropIdx('compounds', 'idx_compounds_q');

UPDATE config SET conf_value = 215 WHERE conf_name = 'schema';
