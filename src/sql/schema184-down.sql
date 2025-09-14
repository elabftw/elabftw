-- revert schema 184

UPDATE config SET conf_value = 183 WHERE conf_name = 'schema';
