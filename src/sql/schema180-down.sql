-- revert schema 180
DELETE FROM config WHERE conf_name = 'allow_team';
DELETE FROM config WHERE conf_name = 'allow_user';
DELETE FROM config WHERE conf_name = 'allow_full';
DELETE FROM config WHERE conf_name = 'allow_organization';
UPDATE config SET conf_value = 179 WHERE conf_name = 'schema';
