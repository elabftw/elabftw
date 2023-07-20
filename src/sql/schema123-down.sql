-- revert schema 123
DELETE FROM config WHERE conf_name = 'user_msg_need_local_account_created';
ALTER TABLE users DROP COLUMN entrypoint;
UPDATE config SET conf_value = 122 WHERE conf_name = 'schema';
