-- schema194
-- fix cases where uploads->userid is different from uploads->entity->userid
-- Experiments uploads with mismatched owner
SELECT
    u.id AS upload_id,
    u.type AS upload_type,
    u.item_id AS upload_experiment_id,
    u.userid AS upload_userid,
    e.userid AS experiment_userid
FROM uploads u
    JOIN experiments e ON e.id = u.item_id
WHERE u.type = 'experiments'
  AND u.userid != e.userid
UNION ALL
-- Items uploads with mismatched owner
SELECT
    u.id AS upload_id,
    u.type AS upload_type,
    u.item_id AS upload_item_id,
    u.userid AS upload_userid,
    i.userid AS item_userid
FROM uploads u
    JOIN items i ON i.id = u.item_id
WHERE u.type = 'items'
  AND u.userid != i.userid;
-- update with correct owner
UPDATE uploads u
LEFT JOIN experiments e
    ON u.type = 'experiments' AND u.item_id = e.id
LEFT JOIN items i
    ON u.type = 'items' AND u.item_id = i.id
SET u.userid = CASE
    WHEN u.type = 'experiments' THEN e.userid
    WHEN u.type = 'items' THEN i.userid
END
WHERE (
    u.type = 'experiments'
    AND e.userid IS NOT NULL
    AND u.userid != e.userid
  ) OR (
    u.type = 'items'
    AND i.userid IS NOT NULL
    AND u.userid != i.userid
  );
