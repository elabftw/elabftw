-- Schema 66
START TRANSACTION;
    CREATE TABLE `favtags2users` IF NOT EXISTS (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `users_id` int UNSIGNED NOT NULL,
      `tags_id` int UNSIGNED NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
    ALTER TABLE `favtags2users` ADD CONSTRAINT `fk_favtags2users_tags_id` FOREIGN KEY (`tags_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
    ALTER TABLE `favtags2users` ADD CONSTRAINT `fk_favtags2users_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;
    UPDATE config SET conf_value = 66 WHERE conf_name = 'schema';
COMMIT;
