-- Schema 87
-- add missing fk/constraints
-- experiments_templates
ALTER TABLE `experiments_templates`
  ADD KEY `fk_experiments_templates_users_userid` (`userid`);
ALTER TABLE `experiments_templates`
  ADD CONSTRAINT `fk_experiments_templates_users_userid`
    FOREIGN KEY (`userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;
-- items
ALTER TABLE `items`
  ADD KEY `fk_items_users_userid` (`userid`);
ALTER TABLE `items`
  ADD CONSTRAINT `fk_items_users_userid`
    FOREIGN KEY (`userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;
UPDATE config SET conf_value = 87 WHERE conf_name = 'schema';
