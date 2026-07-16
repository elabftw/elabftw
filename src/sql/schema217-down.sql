-- revert schema 217
CALL DropColumn('users', 'accent_color');
CALL DropColumn('users', 'accent_foreground');
UPDATE config SET conf_value = 216 WHERE conf_name = 'schema';
