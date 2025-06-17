-- schema 179
CREATE TABLE `experiments_templates_comments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `item_id` int(10) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  `immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_experiments_templates_comments_experiments_templates_id` (`item_id`),
  KEY `fk_experiments_templates_comments_users_userid` (`userid`),
  CONSTRAINT `fk_experiments_templates_comments_experiments_templates_id` FOREIGN KEY (`item_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_experiments_templates_comments_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
ALTER TABLE `experiments_templates` ADD
  `timestamped` tinyint UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `experiments_templates` ADD
  `timestampedby` int(11) NULL DEFAULT NULL;
ALTER TABLE `experiments_templates` ADD
  `timestamped_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `items_types` ADD
  `timestamped` tinyint UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items_types` ADD
  `timestampedby` int(11) NULL DEFAULT NULL;
ALTER TABLE `items_types` ADD
  `timestamped_at` timestamp NULL DEFAULT NULL;
