-- revert schema 151
DROP TABLE IF EXISTS `exports`;
UPDATE `notifications`
  SET `body` = JSON_SET(`body`, '$.entity_page', REPLACE(`body`->>'$.entity_page', '.php', ''))
  WHERE `body`->>'$.entity_page' LIKE '%.php';
UPDATE config SET conf_value = 150 WHERE conf_name = 'schema';
