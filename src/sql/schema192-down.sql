-- revert schema 192
DELETE FROM config where conf_name = 'dspace_host';
DELETE FROM config where conf_name = 'dspace_user';
DELETE FROM config where conf_name = 'dspace_password';
UPDATE config SET conf_value = 191 WHERE conf_name = 'schema';
