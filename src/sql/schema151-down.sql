-- revert schema 151
DROP TABLE IF EXISTS `exports`;
UPDATE config SET conf_value = 150 WHERE conf_name = 'schema';
