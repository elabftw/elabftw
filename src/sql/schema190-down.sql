-- revert schema 190
DELETE FROM config where conf_name = 's3_exports_toggle';
DELETE FROM config where conf_name = 's3_exports_use_dedicated_bucket';
DELETE FROM config where conf_name = 's3_exports_bucket_name';
DELETE FROM config where conf_name = 's3_exports_path_prefix';
DELETE FROM config where conf_name = 's3_exports_region';
DELETE FROM config where conf_name = 's3_exports_endpoint';
DELETE FROM config where conf_name = 's3_exports_verify_cert';
DELETE FROM config where conf_name = 's3_exports_use_path_style_endpoint';
UPDATE config SET conf_value = 189 WHERE conf_name = 'schema';
