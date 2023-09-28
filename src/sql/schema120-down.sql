-- revert schema 120
-- remove keys/constraints
ALTER TABLE `uploads`
  DROP KEY `idx_uploads_item_id_type`,
  DROP KEY `fk_uploads_users_userid`;
-- change column type int to text
ALTER TABLE `uploads` MODIFY `userid` text NOT NULL;
UPDATE config SET conf_value = 119 WHERE conf_name = 'schema';
