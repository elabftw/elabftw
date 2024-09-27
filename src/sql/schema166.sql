-- schema 166
-- fix https://github.com/elabftw/elabftw/issues/5257
-- fix old links which are missing .php extension
-- backend changes happened between schemata 151 and 152
UPDATE `notifications`
  SET `body` = JSON_SET(`body`, '$.entity_page', CONCAT(`body`->>'$.entity_page', '.php'))
  WHERE `body`->>'$.entity_page' NOT LIKE '%.php%';
UPDATE config SET conf_value = 166 WHERE conf_name = 'schema';
