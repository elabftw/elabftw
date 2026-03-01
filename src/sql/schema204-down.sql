CALL DropColumn('items', 'booking_window_days');
UPDATE config SET conf_value = 203 WHERE conf_name = 'schema';
