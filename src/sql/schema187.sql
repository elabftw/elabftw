-- schema 187
ALTER TABLE `users` ADD INDEX `idx_users_email_userid` (email, userid);
ALTER TABLE `users` ADD INDEX `idx_users_orgid_userid` (orgid, userid);
ALTER TABLE `users2teams` ADD INDEX `idx_users_id_is_archived` (`users_id`, `is_archived`);
