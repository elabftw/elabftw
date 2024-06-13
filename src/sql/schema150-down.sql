-- revert schema 150
ALTER TABLE `experiments_request_actions`
  DROP FOREIGN KEY `fk_experiments_request_actions_requester_users_userid`,
  DROP INDEX `fk_experiments_request_actions_requester_users_userid`,
  DROP FOREIGN KEY `fk_experiments_request_actions_target_users_userid`,
  DROP INDEX `fk_experiments_request_actions_target_users_userid`;
ALTER TABLE `items_request_actions`
  DROP FOREIGN KEY `fk_items_request_actions_requester_users_userid`,
  DROP INDEX `fk_items_request_actions_requester_users_userid`,
  DROP FOREIGN KEY `fk_items_request_actions_target_users_userid`,
  DROP INDEX `fk_items_request_actions_target_users_userid`;
UPDATE config SET conf_value = 149 WHERE conf_name = 'schema';
