-- schema 150
ALTER TABLE `experiments_request_actions`
  ADD INDEX `fk_experiments_request_actions_requester_users_userid` (`requester_userid`),
  ADD CONSTRAINT `fk_experiments_request_actions_requester_users_userid`
    FOREIGN KEY (`requester_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD INDEX `fk_experiments_request_actions_target_users_userid` (`target_userid`),
  ADD CONSTRAINT `fk_experiments_request_actions_target_users_userid`
    FOREIGN KEY (`target_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `items_request_actions`
  ADD INDEX `fk_items_request_actions_requester_users_userid` (`requester_userid`),
  ADD CONSTRAINT `fk_items_request_actions_requester_users_userid`
    FOREIGN KEY (`requester_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD INDEX `fk_items_request_actions_target_users_userid` (`target_userid`),
  ADD CONSTRAINT `fk_items_request_actions_target_users_userid`
    FOREIGN KEY (`target_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;
UPDATE config SET conf_value = 150 WHERE conf_name = 'schema';
