-- revert schema 222
ALTER TABLE `users`
    DROP INDEX `idx_users_token_hash`,
    DROP COLUMN `token_hash`,
    ADD COLUMN `token` CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL DEFAULT NULL AFTER `created_at`,
    ADD UNIQUE INDEX `idx_users_token` (`token`);

UPDATE config SET conf_value = 221 WHERE conf_name = 'schema';
