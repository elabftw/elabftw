-- revert schema 178
DELETE FROM config WHERE conf_name = 'local_login_hidden_only_sysadmin';
DELETE FROM config WHERE conf_name = 'local_login_only_sysadmin';
UPDATE config SET conf_value = 177 WHERE conf_name = 'schema';
