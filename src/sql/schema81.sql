-- Schema 81
-- add potentially missing authfail table
START TRANSACTION;
    CREATE TABLE IF NOT EXISTS `authfail` (
      `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `users_id` INT(10) UNSIGNED NOT NULL,
      `attempt_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `device_token` VARCHAR(255) NULL DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
    UPDATE config SET conf_value = 81 WHERE conf_name = 'schema';
COMMIT;
