-- revert schema 169
DROP TABLE calendar2items_types;
DROP TABLE calendar2items;
DROP TABLE calendars;
ALTER TABLE `team_events`
  DROP FOREIGN KEY `fk_team_events_items_id`,
  DROP KEY `fk_team_events_items_id`,
  DROP FOREIGN KEY `fk_team_events_experiments_id`,
  DROP KEY `fk_team_events_experiments_id`,
  DROP FOREIGN KEY `fk_team_events_item_link_id`,
  DROP KEY `fk_team_events_item_link_id`;
UPDATE config SET conf_value = 168 WHERE conf_name = 'schema';
