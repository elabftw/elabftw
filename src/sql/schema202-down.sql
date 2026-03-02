-- revert schema 202
DELETE FROM config WHERE conf_name = 'ldap_sync_teams';
DELETE FROM config WHERE conf_name = 'ldap_team_create';
UPDATE config SET conf_value = 201 WHERE conf_name = 'schema';
