-- schema 152 exclusive lock
CREATE TABLE `experiments_edit_mode` (
  `experiments_id` int UNSIGNED NOT NULL,
  `locked_by` int UNSIGNED NOT NULL,
  `locked_at` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`experiments_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `experiments_edit_mode`
  ADD KEY `idx_experiments_edit_mode_all_columns` (`experiments_id`, `locked_by`, `locked_at`),
  ADD CONSTRAINT `fk_experiments_edit_mode_experiments_id`
    FOREIGN KEY (`experiments_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_edit_mode_users_userid`
    FOREIGN KEY (`locked_by`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE `items_edit_mode` (
  `items_id` int UNSIGNED NOT NULL,
  `locked_by` int UNSIGNED NOT NULL,
  `locked_at` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `items_edit_mode`
  ADD KEY `idx_items_edit_mode_all_columns` (`items_id`, `locked_by`, `locked_at`),
  ADD CONSTRAINT `fk_items_edit_mode_items_id`
    FOREIGN KEY (`items_id`) REFERENCES `items` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_edit_mode_users_userid`
    FOREIGN KEY (`locked_by`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE `items_types_edit_mode` (
  `items_types_id` int UNSIGNED NOT NULL,
  `locked_by` int UNSIGNED NOT NULL,
  `locked_at` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`items_types_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `items_types_edit_mode`
  ADD KEY `idx_items_types_edit_mode_all_columns` (`items_types_id`, `locked_by`, `locked_at`),
  ADD CONSTRAINT `fk_items_types_edit_mode_items_types_id`
    FOREIGN KEY (`items_types_id`) REFERENCES `items_types` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_types_edit_mode_users_userid`
    FOREIGN KEY (`locked_by`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE `experiments_templates_edit_mode` (
  `experiments_templates_id` int UNSIGNED NOT NULL,
  `locked_by` int UNSIGNED NOT NULL,
  `locked_at` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`experiments_templates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `experiments_templates_edit_mode`
  ADD KEY `idx_experiments_templates_edit_mode_all_columns` (`experiments_templates_id`, `locked_by`, `locked_at`),
  ADD CONSTRAINT `fk_experiments_templates_edit_mode_experiments_templates_id`
    FOREIGN KEY (`experiments_templates_id`) REFERENCES `experiments_templates` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_templates_edit_mode_users_userid`
    FOREIGN KEY (`locked_by`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE `experiments_templates_request_actions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `requester_userid` INT UNSIGNED NOT NULL,
  `target_userid` INT UNSIGNED NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `action` INT UNSIGNED NOT NULL,
  `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_experiments_tpl_req_actions_exp_tpl_id` (`entity_id`),
  CONSTRAINT `fk_experiments_tpl_req_actions_exp_tpl_id`
    FOREIGN KEY (`entity_id`) REFERENCES `experiments_templates` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  KEY `fk_experiments_templates_request_actions_requester_users_userid` (`requester_userid`),
  CONSTRAINT `fk_experiments_templates_request_actions_requester_users_userid`
    FOREIGN KEY (`requester_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  KEY `fk_experiments_templates_request_actions_target_users_userid` (`target_userid`),
  CONSTRAINT `fk_experiments_templates_request_actions_target_users_userid`
    FOREIGN KEY (`target_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE);

CREATE TABLE `items_types_request_actions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `requester_userid` INT UNSIGNED NOT NULL,
  `target_userid` INT UNSIGNED NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `action` INT UNSIGNED NOT NULL,
  `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_items_types_request_actions_items_types_id` (`entity_id`),
  CONSTRAINT `fk_items_types_request_actions_items_types_id`
    FOREIGN KEY (`entity_id`) REFERENCES `items_types` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  KEY `fk_items_types_request_actions_requester_users_userid` (`requester_userid`),
  CONSTRAINT `fk_items_types_request_actions_requester_users_userid`
    FOREIGN KEY (`requester_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  KEY `fk_items_types_request_actions_target_users_userid` (`target_userid`),
  CONSTRAINT `fk_items_types_request_actions_target_users_userid`
    FOREIGN KEY (`target_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE);
UPDATE config SET conf_value = 152 WHERE conf_name = 'schema';
