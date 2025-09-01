-- revert schema 182
DELETE FROM config where conf_name = 's3_use_path_style_endpoint'
UPDATE config SET conf_value = 181 WHERE conf_name = 'schema';
