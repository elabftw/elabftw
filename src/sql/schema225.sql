-- schema 225 recurring events
ALTER TABLE `team_events`
    ADD COLUMN `recurrence_series_id` CHAR(36) NULL AFTER `modified_at`,
    ADD COLUMN `recurrence_index` SMALLINT UNSIGNED NULL AFTER `recurrence_series_id`,
    ADD COLUMN `recurrence_frequency` VARCHAR(7) NULL AFTER `recurrence_index`,
    ADD COLUMN `recurrence_interval` SMALLINT UNSIGNED NULL AFTER `recurrence_frequency`,
    ADD CONSTRAINT `chk_team_events_recurrence_metadata`
        CHECK ((`recurrence_series_id` IS NULL AND `recurrence_index` IS NULL AND `recurrence_frequency` IS NULL AND `recurrence_interval` IS NULL)
            OR (`recurrence_series_id` IS NOT NULL AND `recurrence_index` IS NOT NULL
                AND `recurrence_frequency` IS NOT NULL AND `recurrence_interval` IS NOT NULL)),
    ADD UNIQUE INDEX `uniq_team_events_recurrence_series_index` (`recurrence_series_id`, `recurrence_index`);
