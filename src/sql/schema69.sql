-- Schema 69
START TRANSACTION;
    CREATE TABLE `notifications` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `userid` int(10) unsigned NOT NULL,
        `category` int(10) unsigned NOT NULL,
        `send_email` tinyint(1) NOT NULL DEFAULT '0',
        `email_sent` tinyint(1) NOT NULL DEFAULT '0',
        `email_sent_at` datetime DEFAULT NULL,
        `is_ack` tinyint(1) NOT NULL DEFAULT '0',
        `is_ack_at` datetime DEFAULT NULL,
        `body` text NULL,
        PRIMARY KEY (`id`),
        KEY `fk_notifications_users_userid` (`userid`),
        CONSTRAINT `fk_notifications_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE
    );
    UPDATE config SET conf_value = 69 WHERE conf_name = 'schema';
COMMIT;
