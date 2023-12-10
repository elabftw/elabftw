-- schema 136
-- this was introduced during alpha stage, so it is fine to just drop and recreate the table
DROP TABLE IF EXISTS audit_logs;
CREATE TABLE `audit_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `category` INT UNSIGNED NOT NULL,
    `requester_userid` INT UNSIGNED NOT NULL,
    `target_userid` INT UNSIGNED NOT NULL,
    `body` TEXT NOT NULL,
    PRIMARY KEY (`id`));
UPDATE config SET conf_value = 136 WHERE conf_name = 'schema';
