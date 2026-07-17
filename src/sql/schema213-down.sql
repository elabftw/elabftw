-- revert schema 213
CALL DropColumn('experiments', 'signature_count');
CALL DropColumn('experiments', 'last_signed_at');
CALL DropColumn('experiments', 'last_signed_by');
CALL DropColumn('experiments_templates', 'signature_count');
CALL DropColumn('experiments_templates', 'last_signed_at');
CALL DropColumn('experiments_templates', 'last_signed_by');
CALL DropColumn('items', 'signature_count');
CALL DropColumn('items', 'last_signed_at');
CALL DropColumn('items', 'last_signed_by');
CALL DropColumn('items_types', 'signature_count');
CALL DropColumn('items_types', 'last_signed_at');
CALL DropColumn('items_types', 'last_signed_by');

UPDATE config SET conf_value = 212 WHERE conf_name = 'schema';
