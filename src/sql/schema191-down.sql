-- revert schema 191
DELETE FROM config where conf_name = 'users_validity_is_externally_managed';
DELETE FROM config where conf_name = 'admin_panel_custom_msg';
UPDATE config SET conf_value = 190 WHERE conf_name = 'schema';
