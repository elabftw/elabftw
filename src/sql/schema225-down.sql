-- revert schema 225
CALL DropColumn('experiments_categories', 'color_fg');
CALL DropColumn('items_categories', 'color_fg');

UPDATE config SET conf_value = 224 WHERE conf_name = 'schema';
