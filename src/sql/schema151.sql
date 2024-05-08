-- schema 151
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
UPDATE config SET conf_value = 151 WHERE conf_name = 'schema';
