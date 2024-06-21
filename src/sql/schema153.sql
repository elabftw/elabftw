-- schema 153 : make uploads.filesize column bigger
ALTER TABLE `uploads` CHANGE `filesize` `filesize` BIGINT UNSIGNED NULL DEFAULT NULL;
UPDATE config SET conf_value = 153 WHERE conf_name = 'schema';
