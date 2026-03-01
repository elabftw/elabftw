-- revert schema 205
CALL DropColumn('experiments', 'created_from_id');
CALL DropColumn('experiments', 'created_from_type');
CALL DropColumn('experiments_templates', 'created_from_id');
CALL DropColumn('experiments_templates', 'created_from_type');
CALL DropColumn('items', 'created_from_id');
CALL DropColumn('items', 'created_from_type');
CALL DropColumn('items_types', 'created_from_id');
CALL DropColumn('items_types', 'created_from_type');

UPDATE config SET conf_value = 204 WHERE conf_name = 'schema';
