-- Track when a user row was last changed.
-- Existing rows get the migration time: their real last change is unknown.
ALTER TABLE `users`
  ADD COLUMN `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
