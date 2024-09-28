-- revert schema 167
DROP TABLE fingerprints;
UPDATE config SET conf_value = 166 WHERE conf_name = 'schema';
