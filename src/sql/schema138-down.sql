-- revert schema 138
UPDATE config SET conf_value = 137 WHERE conf_name = 'schema';
