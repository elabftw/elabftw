-- revert schema 218
CALL DropColumn('users', 'primary_color');
CALL DropColumn('users', 'primary_foreground');
