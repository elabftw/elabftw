-- Download requests check the upload state by storage filename.
-- 64 chars cover the folder and the uuid: a longer prefix only makes the index bigger.
ALTER TABLE `uploads`
  ADD INDEX `idx_uploads_long_name_state` (`long_name`(64), `state`);
