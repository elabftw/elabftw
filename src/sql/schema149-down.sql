-- revert schema 149
DROP TABLE experiments_templates2experiments;
UPDATE config SET conf_value = 148 WHERE conf_name = 'schema';
