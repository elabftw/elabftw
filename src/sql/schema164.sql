-- schema 164
DELETE FROM config WHERE conf_name = 'trust_imported_archives';
UPDATE config SET conf_value = 164 WHERE conf_name = 'schema';
