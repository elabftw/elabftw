-- revert migration 01a1a33f-f2fc-710d-a422-d0885590c009

DELETE FROM config WHERE conf_name = 'admins_impersonate_users';
