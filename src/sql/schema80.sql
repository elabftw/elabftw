-- Schema 80
-- drop some unnecessary id columns part 1
START TRANSACTION;
    -- favtags2users change PRIMARY KEY
    ALTER TABLE `favtags2users` DROP `id`;
    ALTER TABLE `favtags2users` ADD PRIMARY KEY(`users_id`, `tags_id`);
    -- users2teams change PRIMARY KEY
    ALTER TABLE `users2teams` DROP `id`;
    ALTER TABLE `users2teams` ADD PRIMARY KEY(`users_id`, `teams_id`);
    -- users2team_groups change PRIMARY KEY
    ALTER TABLE `users2team_groups` DROP `id`;
    ALTER TABLE `users2team_groups` ADD PRIMARY KEY(`userid`, `groupid`);
    -- users2team_groups add FKs
    ALTER TABLE `users2team_groups`
      ADD KEY `fk_users2team_groups_groupid` (`groupid`),
      ADD KEY `fk_users2team_groups_userid` (`userid`);
    -- users2team_groups add constraints
    ALTER TABLE `users2team_groups`
      ADD CONSTRAINT `fk_users2team_groups_groupid` FOREIGN KEY (`groupid`) REFERENCES `team_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
      ADD CONSTRAINT `fk_users2team_groups_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;
    UPDATE config SET conf_value = 80 WHERE conf_name = 'schema';
COMMIT;
