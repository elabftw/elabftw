-- revert schema 209
DROP TABLE IF EXISTS `storage_units_history`;
UPDATE config SET conf_value = 208 WHERE conf_name = 'schema';
