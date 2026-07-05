-- schema 215
CALL DropColumn('experiments_status', 'is_default');
CALL DropColumn('experiments_categories', 'is_default');
CALL DropColumn('items_status', 'is_default');
CALL DropColumn('items_categories', 'is_default');
