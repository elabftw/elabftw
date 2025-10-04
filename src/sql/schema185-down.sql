-- revert schema 185
DELETE FROM `config` WHERE `conf_name` = 'compounds_require_edit_rights';
DELETE FROM `config` WHERE `conf_name` = 'inventory_require_edit_rights';
UPDATE config SET conf_value = 184 WHERE conf_name = 'schema';
