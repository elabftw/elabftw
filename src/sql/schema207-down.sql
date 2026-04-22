-- revert schema 207
CALL DropColumn('teams', 'force_res_tpl');
UPDATE config SET conf_value = 206 WHERE conf_name = 'schema';
