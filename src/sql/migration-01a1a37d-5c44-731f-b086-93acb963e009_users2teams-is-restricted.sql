-- A user restricted in a team only sees entries they own or that are explicitly shared with them (user or teamgroup)
-- while acting in that team. Set per team so a student in one team can be a regular member of another.
ALTER TABLE `users2teams`
  ADD COLUMN `is_restricted` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `is_archived`;
