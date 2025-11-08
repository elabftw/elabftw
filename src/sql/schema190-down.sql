-- revert schema 190
DELETE FROM config where conf_name = 's3_exports';
DELETE FROM config where conf_name = 's3_exports_path';
UPDATE config SET conf_value = 189 WHERE conf_name = 'schema';
