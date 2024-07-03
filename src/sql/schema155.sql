-- schema 155
ALTER TABLE `items_types`
  ADD `locked` tinyint UNSIGNED NOT NULL DEFAULT 0,
  ADD `lockedby` int UNSIGNED DEFAULT NULL,
  ADD `locked_at` timestamp NULL DEFAULT NULL;

UPDATE config SET conf_value = 155 WHERE conf_name = 'schema';
