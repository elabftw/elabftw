-- revert schema 168
ALTER TABLE `api_keys` DROP FOREIGN KEY `fk_api_keys_user_team`;
UPDATE config SET conf_value = 167 WHERE conf_name = 'schema';
