-- the user groups listed in the permissions modal no longer depend on a user setting
ALTER TABLE users DROP COLUMN scope_teamgroups;
