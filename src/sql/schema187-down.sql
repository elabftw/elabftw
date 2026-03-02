-- revert schema 187
CALL DropIdx('users', 'idx_users_email_userid');
CALL DropIdx('users', 'idx_users_orgid_userid');
CALL DropIdx('users2teams', 'idx_users_id_is_archived');
UPDATE config SET conf_value = 186 WHERE conf_name = 'schema';
