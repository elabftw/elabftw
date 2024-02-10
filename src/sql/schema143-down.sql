-- revert schema 143 - nothing to do
UPDATE config SET conf_value = 142 WHERE conf_name = 'schema';
