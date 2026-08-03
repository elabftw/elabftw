-- revert schema 217
CALL DropIdx('users', 'idx_users_token');
ALTER TABLE users
    MODIFY token VARCHAR(255) NULL DEFAULT NULL;

UPDATE config SET conf_value = 216 WHERE conf_name = 'schema';
