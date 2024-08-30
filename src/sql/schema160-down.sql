-- revert schema 160
DELETE FROM `config` WHERE conf_name = 'ldap_scheme';
UPDATE config SET conf_value = 159 WHERE conf_name = 'schema';
