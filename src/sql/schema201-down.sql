-- revert schema 201
CALL DropColumn('experiments_categories', 'is_private');
CALL DropColumn('experiments_status', 'is_private');
CALL DropColumn('items_categories', 'is_private');
CALL DropColumn('items_status', 'is_private');
UPDATE config SET conf_value = 200 WHERE conf_name = 'schema';
