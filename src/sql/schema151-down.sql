-- revert schema 151
DROP TABLE `experiments_edit_mode`;
DROP TABLE `items_edit_mode`;
UPDATE config SET conf_value = 150 WHERE conf_name = 'schema';
