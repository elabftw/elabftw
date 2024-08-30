-- revert schema 152
DROP TABLE `experiments_edit_mode`;
DROP TABLE `items_edit_mode`;
DROP TABLE `experiments_templates_edit_mode`;
DROP TABLE `items_types_edit_mode`;
DROP TABLE `experiments_templates_request_actions`;
DROP TABLE `items_types_request_actions`;
UPDATE config SET conf_value = 151 WHERE conf_name = 'schema';
