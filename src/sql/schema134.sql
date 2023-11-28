-- schema 134
CREATE TABLE `audit_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `body` TEXT NOT NULL,
    `category` INT UNSIGNED NOT NULL,
    `userid` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`));
UPDATE config SET conf_value = 134 WHERE conf_name = 'schema';
