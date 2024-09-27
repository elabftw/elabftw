-- revert schema 166
-- nothing to do here but could be:
-- UPDATE `notifications`
--   SET `body` = JSON_SET(`body`, '$.entity_page', REPLACE(`body`->>'$.entity_page', '.php', ''))
--   WHERE `body`->>'$.entity_page' LIKE '%.php';
UPDATE config SET conf_value = 165 WHERE conf_name = 'schema';
