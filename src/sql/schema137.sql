-- schema 137
DELETE FROM config WHERE conf_name = 'deletable_xp';
ALTER TABLE teams DROP deletable_xp;
ALTER TABLE teams DROP deletable_item;
ALTER TABLE experiments_templates ADD canread_target JSON NOT NULL;
UPDATE experiments_templates SET canread_target = canread;
ALTER TABLE experiments_templates ADD canwrite_target JSON NOT NULL;
UPDATE experiments_templates SET canwrite_target = canwrite;
ALTER TABLE items_types ADD canread_target JSON NOT NULL;
UPDATE items_types SET canread_target = canread;
ALTER TABLE items_types ADD canwrite_target JSON NOT NULL;
UPDATE items_types SET canwrite_target = canwrite;
UPDATE config SET conf_value = 137 WHERE conf_name = 'schema';
