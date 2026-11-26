-- revert migration 01a1ee44-25e7-7942-b8ac-e02803859da4

CALL DropColumn('items', 'booking_hours');
