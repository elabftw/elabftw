START TRANSACTION;
    CREATE TABLE `pin2users` ( `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, `users_id` INT UNSIGNED NOT NULL , `entity_id` INT UNSIGNED NOT NULL , `type` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`),
    KEY `fk_pin2users_userid` (`users_id`),
    CONSTRAINT `fk_pin2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE);
    UPDATE config SET conf_value = 54 WHERE conf_name = 'schema';
COMMIT;

