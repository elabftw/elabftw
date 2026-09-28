-- revert schema 227
ALTER TABLE `team_events`
    DROP CHECK `chk_team_events_recurrence_metadata`;
CALL DropIdx('team_events', 'uniq_team_events_recurrence_index');
ALTER TABLE `team_events`
    DROP COLUMN `recurrence_rule`,
    DROP COLUMN `recurrence_interval`,
    DROP COLUMN `recurrence_frequency`,
    DROP COLUMN `recurrence_index`,
    DROP COLUMN `recurrence_id`;

UPDATE config SET conf_value = 226 WHERE conf_name = 'schema';
