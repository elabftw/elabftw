-- migration 01a1a33f-f2fc-710d-a422-d0885590c009

INSERT IGNORE INTO config (conf_name, conf_value) VALUES ('admins_impersonate_users', '0');
