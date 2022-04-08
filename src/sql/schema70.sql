-- Schema 70
START TRANSACTION;
    CREATE TABLE `notifications` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `userid` int(10) unsigned NOT NULL,
        `category` int(10) unsigned NOT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `send_email` tinyint(1) NOT NULL DEFAULT '0',
        `email_sent` tinyint(1) NOT NULL DEFAULT '0',
        `email_sent_at` datetime DEFAULT NULL,
        `is_ack` tinyint(1) NOT NULL DEFAULT '0',
        `body` json NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `fk_notifications_users_userid` (`userid`),
        CONSTRAINT `fk_notifications_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE
    );
    ALTER TABLE `users` ADD `notif_comment_created` tinyint(1) NOT NULL DEFAULT '1';
    ALTER TABLE `users` ADD `notif_comment_created_email` tinyint(1) NOT NULL DEFAULT '1';
    ALTER TABLE `users` ADD `notif_user_created` tinyint(1) NOT NULL DEFAULT '1';
    ALTER TABLE `users` ADD `notif_user_created_email` tinyint(1) NOT NULL DEFAULT '1';
    ALTER TABLE `users` ADD `notif_user_need_validation` tinyint(1) NOT NULL DEFAULT '1';
    ALTER TABLE `users` ADD `notif_user_need_validation_email` tinyint(1) NOT NULL DEFAULT '1';
    UPDATE config SET conf_value = 70 WHERE conf_name = 'schema';
COMMIT;
