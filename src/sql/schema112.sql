-- schema 112
ALTER TABLE `users2teams` ADD `is_admin` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `users2teams` ADD `is_owner` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `users2teams`
  ADD KEY `idx_users2teams_is_admin` (`is_admin`),
  ADD KEY `idx_users2teams_is_owner` (`is_owner`);
ALTER TABLE `users` ADD `is_sysadmin` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `usergroup`;
ALTER TABLE `users`
  ADD KEY `idx_users_is_sysadmin` (`is_sysadmin`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_archived` (`archived`),
  ADD KEY `idx_users_validated` (`validated`);
UPDATE `users` SET `is_sysadmin` = 1 WHERE `usergroup` = 1;
-- admin users become admins of all teams they are member of. Like it was until now but exclude archived users.
UPDATE `users2teams` AS `u2t`,
  (SELECT `users2teams`.`users_id`, `users2teams`.`teams_id`, `users`.`usergroup`, `users`.`archived`
    FROM `users`
    LEFT JOIN `users2teams`
      ON (`users2teams`.`users_id` = `users`.`userid`)
    WHERE `users`.`usergroup` IN (1, 2)
      AND `users`.`archived` = 0
  ) AS `tmp`
  SET `u2t`.`is_admin` = 1
  WHERE `u2t`.`users_id` = `tmp`.`users_id`
    AND `u2t`.`teams_id` = `tmp`.`teams_id`;
ALTER TABLE `users` DROP FOREIGN KEY `fk_users_groups_id`;
ALTER TABLE `users` DROP COLUMN `usergroup`;
DROP TABLE `groups`;
UPDATE config SET conf_value = 112 WHERE conf_name = 'schema';
