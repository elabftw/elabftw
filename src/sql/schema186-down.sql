-- revert schema 186
-- no need to revert the compounds.name to varchar
UPDATE config SET conf_value = 185 WHERE conf_name = 'schema';
