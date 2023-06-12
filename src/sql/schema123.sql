-- schema 123
UPDATE users2teams SET groups_id = 2 WHERE groups_id = 1;
UPDATE config SET conf_value = 123 WHERE conf_name = 'schema';
