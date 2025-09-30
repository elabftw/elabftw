-- revert schema 184
DropColumn('users', 'can_manage_compounds');
DropColumn('users', 'can_manage_inventory_locations');
UPDATE config SET conf_value = 183 WHERE conf_name = 'schema';
