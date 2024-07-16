-- revert schema 156
ALTER TABLE `teams` DROP COLUMN `newcomer_threshold`;
ALTER TABLE `teams` DROP COLUMN `newcomer_banner`;
ALTER TABLE `teams` DROP COLUMN `newcomer_banner_active`;
ALTER TABLE `users` ADD COLUMN `register_date` bigint(20) UNSIGNED NOT NULL;
UPDATE `users` SET `register_date` = UNIX_TIMESTAMP(`created_at`);
ALTER TABLE `users` DROP COLUMN `created_at`;
UPDATE config SET conf_value = 155 WHERE conf_name = 'schema';
