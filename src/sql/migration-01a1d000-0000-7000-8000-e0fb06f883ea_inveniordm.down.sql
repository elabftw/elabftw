-- revert migration 01a1d000-0000-7000-8000-e0fb06f883ea

DELETE FROM config WHERE conf_name IN ('inveniordm_host', 'inveniordm_name');
CALL DropColumn('users', 'inveniordm_token');
