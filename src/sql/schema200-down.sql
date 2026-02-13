-- revert schema 200
CALL DropColumn('teams', 'users_canwrite_experiments_templates');
CALL DropColumn('teams', 'users_canwrite_resources_templates');
UPDATE config SET conf_value = 199 WHERE conf_name = 'schema';
