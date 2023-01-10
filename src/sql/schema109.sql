-- Schema 109
UPDATE `items` SET `locked` = 0 WHERE `locked` IS NULL;
ALTER TABLE `items` MODIFY `locked` tinyint UNSIGNED NOT NULL DEFAULT 0;
UPDATE config SET conf_value = 109 WHERE conf_name = 'schema';
