-- schema 109
-- change column type text to int
ALTER TABLE `uploads` MODIFY `userid` int UNSIGNED NOT NULL;
-- add a FK/constraints to facilitate joints and where clauses
ALTER TABLE `uploads`
  ADD KEY `fk_uploads_users_userid` (`userid`),
  ADD CONSTRAINT `fk_uploads_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;
UPDATE config SET conf_value = 109 WHERE conf_name = 'schema';
