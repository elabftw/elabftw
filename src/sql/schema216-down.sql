-- revert schema 216
CALL DropColumn('users', 'accent_color');
CALL DropColumn('users', 'accent_foreground');
UPDATE config SET conf_value =215207 WHERE conf_name = 'schema';
