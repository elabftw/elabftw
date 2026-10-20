-- booking approval migration 01a15588-f368-7001-8000-000000000002: user preferences for booking approval notifications
ALTER TABLE `users`
    ADD COLUMN `notif_booking_approval_requested` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ADD COLUMN `notif_booking_approval_requested_email` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ADD COLUMN `notif_booking_approval_updated` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ADD COLUMN `notif_booking_approval_updated_email` TINYINT UNSIGNED NOT NULL DEFAULT 1;
