-- revert booking approval migration 01a15588-ef80-7001-8000-000000000001
CALL DropColumn('users', 'notif_booking_approval_updated_email');
CALL DropColumn('users', 'notif_booking_approval_updated');
CALL DropColumn('users', 'notif_booking_approval_requested_email');
CALL DropColumn('users', 'notif_booking_approval_requested');
CALL DropIdx('team_events', 'idx_team_events_approval');
CALL DropColumn('team_events', 'approval_comment');
CALL DropColumn('team_events', 'approved_at');
CALL DropColumn('team_events', 'approved_by');
CALL DropColumn('team_events', 'approval_status');
DROP TABLE IF EXISTS `items_booking_approvers`;
CALL DropColumn('items', 'requires_booking_approval');
