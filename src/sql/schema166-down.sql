-- revert schema 166
DROP TABLE fingerprints;
UPDATE config SET conf_value = 165 WHERE conf_name = 'schema';
