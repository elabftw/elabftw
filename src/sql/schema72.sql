-- Schema 72
START TRANSACTION;
    ALTER TABLE `uploads` ADD `storage` int(10) UNSIGNED NOT NULL DEFAULT 1;
    ALTER TABLE `uploads` ADD `filesize` int(10) UNSIGNED NULL DEFAULT NULL;
    ALTER TABLE `uploads` CHANGE `datetime` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
    ALTER TABLE `uploads` ADD `state` int(10) UNSIGNED NULL DEFAULT 1;
    UPDATE `uploads` SET `type` = 'experiments' WHERE `type` = 'exp-pdf-timestamp' OR `type` = 'timestamp-token';
    INSERT INTO config (conf_name, conf_value) VALUES ('uploads_storage', '1');
    INSERT INTO config (conf_name, conf_value) VALUES ('s3_bucket_name', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('s3_path_prefix', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('s3_region', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('s3_endpoint', '');
    UPDATE config SET conf_value = 72 WHERE conf_name = 'schema';
COMMIT;
