-- schema 157
UPDATE `users` SET `created_at` = FROM_UNIXTIME(`register_date`);
UPDATE config SET conf_value = 0 WHERE conf_name = 'trust_imported_archives';
UPDATE config SET conf_value = 157 WHERE conf_name = 'schema';
