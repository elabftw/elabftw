-- schema 137
DELETE FROM config WHERE conf_name = 'deletable_xp';
ALTER TABLE teams DROP deletable_xp;
ALTER TABLE teams DROP deletable_item;
UPDATE config SET conf_value = 137 WHERE conf_name = 'schema';
