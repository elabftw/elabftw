-- revert schema 121
UPDATE config SET conf_value = 120 WHERE conf_name = 'schema';
