-- revert schema 180
DELETE FROM config WHERE conf_name = 'allow_permission_team';
DELETE FROM config WHERE conf_name = 'allow_permission_user';
DELETE FROM config WHERE conf_name = 'allow_permission_full';
DELETE FROM config WHERE conf_name = 'allow_permission_organization';
UPDATE config SET conf_name = 'allow_useronly' WHERE conf_name = 'allow_permission_useronly';
UPDATE config SET conf_value = 179 WHERE conf_name = 'schema';
