-- revert schema 167
DROP TABLE fingerprints;
ALTER TABLE experiments DROP COLUMN storage;
ALTER TABLE experiments_templates DROP COLUMN storage;
ALTER TABLE items DROP COLUMN storage;
ALTER TABLE items_types DROP COLUMN storage;
DROP TABLE storage_units;
UPDATE config SET conf_value = 166 WHERE conf_name = 'schema';
