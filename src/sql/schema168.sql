-- schema 168 - fix discrepancies due to invalid user-team combinations
WITH cte AS (
    SELECT id, userid, team FROM `api_keys`
    WHERE (userid, team) NOT IN (
        SELECT users_id, teams_id FROM `users2teams`
    )
)
DELETE FROM `api_keys` WHERE id IN (SELECT id FROM cte);
-- Add constraint to remove associated apikey when user is removed from a team
ALTER TABLE `api_keys` ADD CONSTRAINT `fk_api_keys_user_team` FOREIGN KEY (`userid`, `team`)
    REFERENCES `users2teams` (`users_id`, `teams_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;
UPDATE config SET conf_value = 168 WHERE conf_name = 'schema';
