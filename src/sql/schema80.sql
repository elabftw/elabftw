-- Schema 80
-- drop some unnecessary id columns part 1
-- check data integrity
START TRANSACTION;
    -- favtags2users change PRIMARY KEY
    ALTER TABLE `favtags2users` DROP `id`;
    ALTER TABLE `favtags2users` ADD PRIMARY KEY(`users_id`, `tags_id`);
    -- users2teams change PRIMARY KEY
    ALTER TABLE `users2teams` DROP `id`;
    ALTER TABLE `users2teams` ADD PRIMARY KEY(`users_id`, `teams_id`);
    -- users2team_groups remove row if user does not exists
    DELETE users2team_groups
      FROM users2team_groups
      LEFT JOIN users
        ON (users2team_groups.userid = users.userid)
      WHERE users.userid IS NULL;
    -- users2team_groups remove row if team does not exists
    DELETE users2team_groups
      FROM users2team_groups
      LEFT JOIN team_groups
        ON (users2team_groups.groupid = team_groups.id)
      WHERE team_groups.id IS NULL;
    -- users2team_groups remove duplicates, triplicates, ...
    DELETE FROM users2team_groups
    WHERE id IN (
      SELECT id FROM (
        SELECT MIN(u2tg1.id) as id
        FROM users2team_groups AS u2tg1
        INNER JOIN users2team_groups AS u2tg2
          ON (u2tg1.userid = u2tg2.userid
            AND u2tg1.groupid = u2tg2.groupid
            AND u2tg1.id < u2tg2.id
          )
        GROUP BY u2tg1.id
      ) tmp
    );
    -- users2team_groups change PRIMARY KEY
    ALTER TABLE `users2team_groups` DROP `id`;
    ALTER TABLE `users2team_groups` ADD PRIMARY KEY(`userid`, `groupid`);
    -- users2team_groups add FKs
    ALTER TABLE `users2team_groups`
      ADD KEY `fk_users2team_groups_groupid` (`groupid`),
      ADD KEY `fk_users2team_groups_userid` (`userid`);
    -- users2team_groups add constraints
    ALTER TABLE `users2team_groups`
      ADD CONSTRAINT `fk_users2team_groups_groupid`
        FOREIGN KEY (`groupid`) REFERENCES `team_groups` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
      ADD CONSTRAINT `fk_users2team_groups_userid`
        FOREIGN KEY (`userid`) REFERENCES `users` (`userid`)
        ON DELETE CASCADE ON UPDATE CASCADE;
    UPDATE config SET conf_value = 80 WHERE conf_name = 'schema';
COMMIT;
