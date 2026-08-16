-- schema 222
UPDATE `users` SET `token_created_at` = NULL;
ALTER TABLE `users`
    DROP INDEX `idx_users_token`,
    DROP COLUMN `token`,
    ADD COLUMN `token_hash` BINARY(32) NULL DEFAULT NULL AFTER `created_at`,
    ADD UNIQUE INDEX `idx_users_token_hash` (`token_hash`);
