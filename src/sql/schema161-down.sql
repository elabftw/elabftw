-- revert schema 161
DROP TABLE items_types2experiments;
UPDATE config SET conf_value = 160 WHERE conf_name = 'schema';
