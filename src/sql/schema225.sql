-- schema 225 recurring scheduler bookings
ALTER TABLE `team_events`
    ADD COLUMN `recurrence_series_id` CHAR(36) NULL AFTER `modified_at`,
    ADD COLUMN `recurrence_index` SMALLINT UNSIGNED NULL AFTER `recurrence_series_id`,
    ADD CONSTRAINT `chk_team_events_recurrence_metadata`
        CHECK ((`recurrence_series_id` IS NULL AND `recurrence_index` IS NULL)
            OR (`recurrence_series_id` IS NOT NULL AND `recurrence_index` IS NOT NULL)),
    ADD UNIQUE INDEX `uniq_team_events_recurrence_series_index` (`recurrence_series_id`, `recurrence_index`);
