-- revert schema 153 : no need to revert the filesize column size
UPDATE config SET conf_value = 152 WHERE conf_name = 'schema';
