-- Schema 83
-- only one kind of admin
START TRANSACTION;
    ALTER TABLE `groups` DROP COLUMN `can_lock`;
    UPDATE `users` SET `usergroup` = 2 WHERE `usergroup` = 3;
    DELETE FROM `groups` WHERE `id` = 3;
    UPDATE config SET conf_value = 83 WHERE conf_name = 'schema';
COMMIT;
