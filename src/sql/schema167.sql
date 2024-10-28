-- schema 167
CREATE TABLE `calendars` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `token` varchar(60) NOT NULL,
  `team` int UNSIGNED NOT NULL,
  `created_by` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `state` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `all_events` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `todo` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `unfinished_steps_scope` tinyint UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE `unique_calendars_token` (`token`) USING HASH,
  KEY `fk_calendars_team` (`team`),
  KEY `idx_calendars_todo` (`todo`),
  KEY `idx_calendars_unfinished_steps_scope` (`unfinished_steps_scope`),
  KEY `idx_calendars_state` (`state`),
  KEY `fk_calendars_created_by` (`created_by`),
  CONSTRAINT `fk_calendars_team`
    FOREIGN KEY (`team`) REFERENCES `teams` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_calendars_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `calendar2items` (
  `calendar` int UNSIGNED NOT NULL,
  `item` int UNSIGNED NOT NULL,
  PRIMARY KEY (`calendar`, `item`),
  KEY `fk_calendar2items_calendar` (`calendar`),
  KEY `fk_calendar2items_item` (`item`),
  CONSTRAINT `fk_calendar2items_calendar`
    FOREIGN KEY (`calendar`) REFERENCES `calendars` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_calendar2items_item`
    FOREIGN KEY (`item`) REFERENCES `items` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `calendar2items_types` (
  `calendar` int UNSIGNED NOT NULL,
  `category` int UNSIGNED NOT NULL,
  PRIMARY KEY (`calendar`, `category`),
  KEY `fk_calendar2items_types_calendar` (`calendar`),
  KEY `fk_calendar2items_types_category` (`category`),
  CONSTRAINT `fk_calendar2items_types_calendar`
    FOREIGN KEY (`calendar`) REFERENCES `calendars` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_calendar2items_types_category`
    FOREIGN KEY (`category`) REFERENCES `items_types` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `team_events`
  ADD KEY `fk_team_events_items_id` (`item`),
  ADD CONSTRAINT `fk_team_events_items_id`
    FOREIGN KEY (`item`) REFERENCES `items` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD KEY `fk_team_events_experiments_id` (`experiment`),
  ADD CONSTRAINT `fk_team_events_experiments_id`
    FOREIGN KEY (`experiment`) REFERENCES `experiments` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  ADD KEY `fk_team_events_item_link_id` (`item_link`),
  ADD CONSTRAINT `fk_team_events_item_link_id`
    FOREIGN KEY (`item_link`) REFERENCES `items` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

UPDATE config SET conf_value = 167 WHERE conf_name = 'schema';
