-- revert schema 225
ALTER TABLE `team_events`
    DROP CHECK `chk_team_events_recurrence_metadata`;
CALL DropIdx('team_events', 'uniq_team_events_recurrence_series_index');
ALTER TABLE `team_events`
    DROP COLUMN `recurrence_index`,
    DROP COLUMN `recurrence_series_id`;

UPDATE config SET conf_value = 224 WHERE conf_name = 'schema';
