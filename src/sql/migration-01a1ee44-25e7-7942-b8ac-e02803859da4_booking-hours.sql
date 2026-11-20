-- migration 01a1ee44-25e7-7942-b8ac-e02803859da4

-- days and hours during which a resource can be booked, NULL means no restriction. Format: see Check::bookingHours()
ALTER TABLE `items` ADD COLUMN `booking_hours` JSON NULL DEFAULT NULL AFTER `booking_window_days`;
