-- schema 164
DELETE FROM config WHERE conf_name = 'trust_imported_archives';
ALTER TABLE `items_types` ADD `rating` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `experiments_templates` ADD `rating` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items_types` ADD `userid` INT UNSIGNED NOT NULL;
-- find an admin in that team and assign the userid to that user
UPDATE items_types it
JOIN (
    SELECT teams_id, users_id
    FROM users2teams
    WHERE groups_id = 2
    GROUP BY teams_id, users_id
) admin_users ON it.team = admin_users.teams_id
SET it.userid = admin_users.users_id;
UPDATE config SET conf_value = 164 WHERE conf_name = 'schema';
