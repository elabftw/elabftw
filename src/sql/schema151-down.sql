-- revert schema 151

UPDATE config SET conf_value = 150 WHERE conf_name = 'schema';
