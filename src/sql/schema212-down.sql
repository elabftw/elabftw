-- revert schema 212

UPDATE config SET conf_value = 211 WHERE conf_name = 'schema';
