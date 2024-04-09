-- schema 148
ALTER TABLE users ADD sig_pubkey TEXT NULL DEFAULT NULL;
ALTER TABLE users ADD sig_privkey TEXT NULL DEFAULT NULL;
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
UPDATE config SET conf_value = 148 WHERE conf_name = 'schema';
