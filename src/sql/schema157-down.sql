-- revert schema 157
DELETE FROM config WHERE conf_name = 'trust_imported_archives';
UPDATE config SET conf_value = 156 WHERE conf_name = 'schema';
