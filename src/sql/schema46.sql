-- Schema 46
START TRANSACTION;
    CREATE TABLE `api_keys` (
        `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
        `name` VARCHAR(255) NOT NULL ,
        `hash` VARCHAR(255) NOT NULL ,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL ,
        `can_write` TINYINT(1) NOT NULL DEFAULT 0,
        `userid` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (`id`)
    );
    ALTER TABLE `api_keys` ADD CONSTRAINT `fk_api_keys_users_id` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE cascade ON UPDATE cascade;
    ALTER TABLE `users` DROP `api_key`;
    UPDATE config SET conf_value = 46 WHERE conf_name = 'schema';
COMMIT;
