-- revert schema 224
CALL DropIdx('tags2entity', 'idx_tags2entity_tag_type_item');

UPDATE config SET conf_value = 223 WHERE conf_name = 'schema';
