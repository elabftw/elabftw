-- booking approval migration 01a15588-ef80-7001-8000-000000000001: approval of resource bookings
-- Approval required (0: no, 1: yes); approvers defined in the linking table
ALTER TABLE `items`
    ADD COLUMN `requires_booking_approval` TINYINT UNSIGNED NOT NULL DEFAULT 0;

-- Many-to-many relation: each resource can have multiple user approvers,
-- and each user can approve bookings for multiple resources.
CREATE TABLE `items_booking_approvers` (
    `item_id` INT UNSIGNED NOT NULL,
    `userid` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`item_id`, `userid`),
    INDEX `idx_items_booking_approvers_userid` (`userid`),
    CONSTRAINT `fk_items_booking_approvers_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_items_booking_approvers_user` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 0: no approval, 1: pending, 2: approved, 3: rejected. Overdue is display-only.
-- Existing bookings do not require approval.
ALTER TABLE `team_events`
    ADD COLUMN `approval_status` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN `approved_by` INT UNSIGNED NULL DEFAULT NULL,
    ADD COLUMN `approved_at` DATETIME NULL DEFAULT NULL,
    ADD COLUMN `approval_comment` TEXT NULL DEFAULT NULL,
    ADD INDEX `idx_team_events_approval` (`item`, `approval_status`, `start`);
-- User preferences for booking approval notifications
ALTER TABLE `users`
    ADD COLUMN `notif_booking_approval_requested` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ADD COLUMN `notif_booking_approval_requested_email` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ADD COLUMN `notif_booking_approval_updated` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ADD COLUMN `notif_booking_approval_updated_email` TINYINT UNSIGNED NOT NULL DEFAULT 1;
