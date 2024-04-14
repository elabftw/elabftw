-- schema 148
CREATE TABLE IF NOT EXISTS `sig_keys` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `pubkey` TEXT NULL DEFAULT NULL,
  `privkey` TEXT NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `userid` int UNSIGNED NOT NULL,
  `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `type` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_sig_keys_users_userid` (`userid`),
  CONSTRAINT `fk_sig_keys_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE);
ALTER TABLE experiments_comments ADD immutable TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE items_comments ADD immutable TINYINT UNSIGNED NOT NULL DEFAULT 0;
CREATE TABLE IF NOT EXISTS `experiments_request_actions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `requester_userid` INT UNSIGNED NOT NULL,
    `target_userid` INT UNSIGNED NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `action` INT UNSIGNED NOT NULL,
    `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `fk_experiments_request_actions_experiments_id` (`entity_id`),
    CONSTRAINT `fk_experiments_request_actions_experiments_id` FOREIGN KEY (`entity_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE);
CREATE TABLE IF NOT EXISTS `items_request_actions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `requester_userid` INT UNSIGNED NOT NULL,
    `target_userid` INT UNSIGNED NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `action` INT UNSIGNED NOT NULL,
    `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `fk_items_request_actions_items_id` (`entity_id`),
    CONSTRAINT `fk_items_request_actions_items_id` FOREIGN KEY (`entity_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE);
ALTER TABLE `items` ADD `is_procurable` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items` ADD `proc_pack_qty` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items` ADD `proc_price_notax` DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00;
ALTER TABLE `items` ADD `proc_price_tax` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00;
ALTER TABLE `items` ADD `proc_currency` TINYINT UNSIGNED NOT NULL DEFAULT 0;
CREATE TABLE IF NOT EXISTS `procurement_requests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `team` INT UNSIGNED NOT NULL,
    `requester_userid` INT UNSIGNED NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `qty_ordered` INT UNSIGNED NOT NULL DEFAULT 1,
    `qty_received` INT UNSIGNED NOT NULL DEFAULT 0,
    `body` TEXT NULL DEFAULT NULL,
    `quote` INT UNSIGNED NULL DEFAULT NULL,
    `email_sent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `email_sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `state` TINYINT UNSIGNED NOT NULL DEFAULT 10,
    PRIMARY KEY (`id`),
    KEY `fk_teams_id_proc_team` (`team`),
    KEY `fk_items_id_entity_id` (`entity_id`),
    CONSTRAINT `fk_teams_id_proc_team` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_items_id_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE);
UPDATE config SET conf_value = 148 WHERE conf_name = 'schema';
