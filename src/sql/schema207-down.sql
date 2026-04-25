-- revert schema 207
CALL DropColumn('items', 'booking_hourly_rate_notax');
CALL DropColumn('items', 'booking_hourly_rate_tax');
CALL DropColumn('items', 'booking_hourly_rate_currency');
UPDATE config SET conf_value = 206 WHERE conf_name = 'schema';
