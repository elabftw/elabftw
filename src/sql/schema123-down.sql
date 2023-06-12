-- revert schema 123

UPDATE config SET conf_value = 122 WHERE conf_name = 'schema';
