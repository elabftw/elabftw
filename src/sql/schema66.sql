-- Schema 66
START TRANSACTION;
    CREATE TABLE `favtags2users` (
      `users_id` int(10) UNSIGNED NOT NULL,
      `tags_id` int(10) UNSIGNED NOT NULL
    );
    ALTER TABLE `favtags2users` ADD CONSTRAINT `fk_favtags2users_tags_id` FOREIGN KEY (`tags_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
    ALTER TABLE `favtags2users` ADD CONSTRAINT `fk_favtags2users_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;
    UPDATE config SET conf_value = 66 WHERE conf_name = 'schema';
COMMIT;
