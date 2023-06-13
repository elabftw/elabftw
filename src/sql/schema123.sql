-- schema 123
UPDATE users2teams SET groups_id = 2 WHERE groups_id = 1;
INSERT INTO config (conf_name, conf_value) VALUES ('user_msg_need_local_account_created', '');
UPDATE config SET conf_value = 123 WHERE conf_name = 'schema';
