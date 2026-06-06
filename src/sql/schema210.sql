-- schema 210
DELETE t
FROM tags2entity AS t
JOIN (
  SELECT item_type, item_id, tag_id, MIN(id) AS keep_id
  FROM tags2entity
  GROUP BY item_type, item_id, tag_id
  HAVING COUNT(*) > 1
) AS d
  ON d.item_type = t.item_type
 AND d.item_id = t.item_id
 AND d.tag_id = t.tag_id
WHERE t.id <> d.keep_id;

CREATE UNIQUE INDEX uniq_tags2entity_type_item_tag
ON tags2entity (item_type, item_id, tag_id);
