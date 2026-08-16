-- schema 223
ALTER TABLE `api_keys`
    MODIFY COLUMN `hash` VARCHAR(255) NULL DEFAULT NULL,
    ADD COLUMN `token_hash` BINARY(32) NULL DEFAULT NULL AFTER `hash`,
    ADD UNIQUE INDEX `idx_api_keys_token_hash` (`token_hash`);
