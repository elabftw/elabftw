-- revert schema 198
CALL DropColumn('users', 'theme_variant');
UPDATE config SET conf_value = 197 WHERE conf_name = 'schema';
