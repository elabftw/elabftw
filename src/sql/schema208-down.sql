-- revert schema 208
CALL DropColumn('teams', 'force_res_tpl');
UPDATE config SET conf_value = 207 WHERE conf_name = 'schema';
