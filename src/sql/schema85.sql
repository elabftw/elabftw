-- Schema 85
-- add missing FK and constraints users.usergroup -> groups.id
START TRANSACTION;
    -- TINYINT is more than enough for 3 entries
    ALTER TABLE `users` MODIFY `usergroup` TINYINT UNSIGNED NOT NULL;
    ALTER TABLE `groups` MODIFY `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT;
    -- add FK and constraints
    ALTER TABLE `users`
      ADD KEY `fk_users_groups_id` (`usergroup`);
    ALTER TABLE `users`
      ADD CONSTRAINT `fk_users_groups_id`
        FOREIGN KEY (`usergroup`) REFERENCES `groups` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE;
    UPDATE config SET conf_value = 85 WHERE conf_name = 'schema';
COMMIT;
