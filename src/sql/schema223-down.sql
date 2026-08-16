-- revert schema 223
-- API keys created or migrated on schema 223 cannot be converted back to bcrypt.
-- Preserve their rows but make those credentials unusable after a downgrade.
UPDATE `api_keys` SET `hash` = CONCAT('disabled-', UUID()) WHERE `hash` IS NULL;
CALL DropIdx('api_keys', 'idx_api_keys_token_hash');
CALL DropColumn('api_keys', 'token_hash');
ALTER TABLE `api_keys` MODIFY COLUMN `hash` VARCHAR(255) NOT NULL;
UPDATE config SET conf_value = 222 WHERE conf_name = 'schema';
