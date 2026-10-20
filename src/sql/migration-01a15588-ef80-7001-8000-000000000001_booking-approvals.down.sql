-- revert booking approval migration 01a15588-ef80-7001-8000-000000000001
CALL DropIdx('team_events', 'idx_team_events_approval');
CALL DropColumn('team_events', 'approval_comment');
CALL DropColumn('team_events', 'approval_decided_at');
CALL DropColumn('team_events', 'approval_decided_by');
CALL DropColumn('team_events', 'approval_status');
DROP TABLE IF EXISTS `items_booking_validators`;
CALL DropColumn('items', 'requires_validation');
