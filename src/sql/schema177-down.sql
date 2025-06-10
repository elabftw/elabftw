-- revert schema 177
DELETE FROM config WHERE conf_name = 'local_login_hidden_only_sysadmin';
DELETE FROM config WHERE conf_name = 'local_login_only_sysadmin';
UPDATE config SET conf_value = 176 WHERE conf_name = 'schema';
