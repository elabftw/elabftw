-- revert schema 218
CALL DropColumn('users', 'primary_color');
CALL DropColumn('users', 'primary_foreground');
UPDATE config SET conf_value = 217 WHERE conf_name = 'schema';
