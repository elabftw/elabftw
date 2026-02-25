-- revert schema 203
UPDATE config SET conf_value = 202 WHERE conf_name = 'schema';
