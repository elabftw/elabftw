-- revert schema 130
DELETE FROM config WHERE conf_name = 'admins_import_users';
UPDATE config SET conf_value = 129 WHERE conf_name = 'schema';
