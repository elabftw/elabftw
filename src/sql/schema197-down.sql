-- revert schema 197
-- put base back into JSON
UPDATE experiments
SET
  canread  = JSON_SET(canread,  '$.base', canread_base),
  canwrite = JSON_SET(canwrite, '$.base', canwrite_base);

UPDATE experiments_templates
SET
  canread  = JSON_SET(canread,  '$.base', canread_base),
  canwrite = JSON_SET(canwrite, '$.base', canwrite_base);

UPDATE items
SET
  canread  = JSON_SET(canread,  '$.base', canread_base),
  canbook  = JSON_SET(canbook,  '$.base', canbook_base),
  canwrite = JSON_SET(canwrite, '$.base', canwrite_base);

-- items_types has no canbook json; base for canbook is derived from canread_base
UPDATE items_types
SET
  canread  = JSON_SET(canread,  '$.base', canread_base),
  canwrite = JSON_SET(canwrite, '$.base', canwrite_base);

-- drop indexes using DropIdx procedure (if they exist)
CALL DropIdx('experiments',            'idx_experiments_canread_base');
CALL DropIdx('experiments',            'idx_experiments_canwrite_base');

CALL DropIdx('experiments_templates',  'idx_experiments_tmpl_canread_base');
CALL DropIdx('experiments_templates',  'idx_experiments_tmpl_canwrite_base');

CALL DropIdx('items',                  'idx_items_canread_base');
CALL DropIdx('items',                  'idx_items_canbook_base');
CALL DropIdx('items',                  'idx_items_canwrite_base');

CALL DropIdx('items_types',            'idx_items_types_canread_base');
CALL DropIdx('items_types',            'idx_items_types_canbook_base');
CALL DropIdx('items_types',            'idx_items_types_canwrite_base');

-- drop columns
CALL DropColumn('experiments',           'canread_base');
CALL DropColumn('experiments',           'canwrite_base');

CALL DropColumn('experiments_templates', 'canread_base');
CALL DropColumn('experiments_templates', 'canwrite_base');
CALL DropColumn('experiments_templates', 'canread_target_base');
CALL DropColumn('experiments_templates', 'canwrite_target_base');

CALL DropColumn('items',                 'canread_base');
CALL DropColumn('items',                 'canbook_base');
CALL DropColumn('items',                 'canwrite_base');

CALL DropColumn('items_types',           'canread_base');
CALL DropColumn('items_types',           'canbook_base');
CALL DropColumn('items_types',           'canwrite_base');
CALL DropColumn('items_types',           'canbook');
CALL DropColumn('items_types',           'canbook_base');
CALL DropColumn('items_types', 'canread_target_base');
CALL DropColumn('items_types', 'canwrite_target_base');

CALL DropColumn('users',           'default_read_base');
CALL DropColumn('users',           'default_write_base');

UPDATE config SET conf_value = 196 WHERE conf_name = 'schema';
