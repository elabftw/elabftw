-- revert schema 221
CALL DropColumn('teams', 'capture_container_deletion_reason');
UPDATE config SET conf_value = 220 WHERE conf_name = 'schema';
