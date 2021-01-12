-- Schema 58

START TRANSACTION;
    ALTER TABLE experiments
    ADD bloxberg_timestamped tinyint(1) NOT NULL DEFAULT '0',
    ADD bloxberg_proof text,
    ADD os_timestamped tinyint(1) NOT NULL DEFAULT '0',
    ADD os_proof_received tinyint(1) NOT NULL DEFAULT '0',
    ADD os_proof text,
    CHANGE datetime datetime TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
    
    ALTER TABLE teams
    ADD os_activate tinyint(1) UNSIGNED NOT NULL DEFAULT 2,
    ADD os_api_key text,
    ADD bloxberg_activate tinyint(1) UNSIGNED NOT NULL DEFAULT 2;

    INSERT IGNORE INTO config (conf_name, conf_value) VALUES ('os_api_key', '');
    INSERT IGNORE INTO config (conf_name, conf_value) VALUES ('os_activate', '0');
    INSERT IGNORE INTO config (conf_name, conf_value) VALUES ('bloxberg_activate', '0');
    UPDATE `config` SET `conf_value` = 58 WHERE `conf_name` = 'schema';
COMMIT;
