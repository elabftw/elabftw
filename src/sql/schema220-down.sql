-- revert schema 220
CALL DropColumn('storage_units', 'capacity');
UPDATE config SET conf_value = 219 WHERE conf_name = 'schema';
