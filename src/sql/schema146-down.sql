-- revert schema 146
ALTER TABLE `items` DROP COLUMN `book_users_can_in_past`;
UPDATE config SET conf_value = 145 WHERE conf_name = 'schema';
