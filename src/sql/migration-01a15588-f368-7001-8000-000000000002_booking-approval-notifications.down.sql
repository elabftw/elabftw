-- revert booking approval migration 01a15588-f368-7001-8000-000000000002
CALL DropColumn('users', 'notif_booking_approval_updated_email');
CALL DropColumn('users', 'notif_booking_approval_updated');
CALL DropColumn('users', 'notif_booking_approval_requested_email');
CALL DropColumn('users', 'notif_booking_approval_requested');
