-- revert schema 207
CALL DropIdx('favtags2users', 'idx_favtags2users_user_tag');
CALL DropIdx('tags2entity', 'uniq_tags2entity_type_item_tag');

UPDATE config SET conf_value = 206 WHERE conf_name = 'schema';
