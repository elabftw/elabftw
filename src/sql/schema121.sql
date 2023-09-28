-- schema 121
-- make sure we only have groups_id of 2 or 4 in there
UPDATE users2teams SET groups_id = 2 WHERE groups_id = 1;
UPDATE config SET conf_value = 121 WHERE conf_name = 'schema';
