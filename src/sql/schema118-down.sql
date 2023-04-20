-- revert schema 118
DELETE FROM config WHERE conf_name = 's3_verify_cert';
UPDATE config SET conf_value = 117 WHERE conf_name = 'schema';
