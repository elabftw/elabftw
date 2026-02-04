-- revert schema 199
CALL DropColumn('teams', 'users_canwrite_experiments_templates');
CALL DropColumn('teams', 'users_canwrite_resources_templates');
UPDATE config SET conf_value = 198 WHERE conf_name = 'schema';
