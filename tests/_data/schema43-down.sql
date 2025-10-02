-- this file is here to test the schema downgrade db:revertto command
UPDATE config SET conf_value = 42 WHERE conf_name = 'schema';
