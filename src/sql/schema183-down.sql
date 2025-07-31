-- revert schema 183

UPDATE config SET conf_value = 182 WHERE conf_name = 'schema';
