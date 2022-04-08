-- Schema 81
-- add user setting for event deletion notification
START TRANSACTION;
    ALTER TABLE `users` ADD `notif_event_deleted` tinyint(1) UNSIGNED NOT NULL DEFAULT 1;
    ALTER TABLE `users` ADD `notif_event_deleted_email` tinyint(1) UNSIGNED NOT NULL DEFAULT 1;
    UPDATE config SET conf_value = 81 WHERE conf_name = 'schema';
COMMIT;
