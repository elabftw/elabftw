-- this file is here to test the schema downgrade db:revert command
UPDATE config SET conf_value = 41 WHERE conf_name = 'schema';
