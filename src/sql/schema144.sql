-- schema 144
ALTER TABLE `experiments` ADD `team` INT UNSIGNED NOT NULL;
-- try and find a team value for experiments, first assign it through status or category
-- this should take care of most experiments
UPDATE experiments e
INNER JOIN users2teams ut
    ON (e.userid = ut.users_id)
LEFT JOIN experiments_status es
    ON (es.id = e.status
        AND es.team = ut.teams_id)
LEFT JOIN experiments_categories ec
    ON (ec.id = e.category
        AND ec.team = ut.teams_id)
SET e.team = ut.teams_id
WHERE es.team IS NOT NULL
    OR ec.team IS NOT NULL;
-- now for the ones that are still 0, fetch a team from the user
UPDATE experiments e
INNER JOIN (
    SELECT users_id, MIN(teams_id) as team
    FROM users2teams
    GROUP BY users_id
) ut ON e.userid = ut.users_id
SET e.team = ut.team
WHERE e.team = 0;
UPDATE config SET conf_value = 144 WHERE conf_name = 'schema';
