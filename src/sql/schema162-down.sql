-- revert schema 162
DELETE FROM config WHERE conf_name = 'allow_users_change_identity';
UPDATE config SET conf_value = 161 WHERE conf_name = 'schema';
