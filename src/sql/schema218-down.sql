-- revert schema 218
CALL DropColumn('users', 'accent_color');
CALL DropColumn('users', 'accent_foreground');
