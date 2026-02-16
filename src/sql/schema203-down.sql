CALL DropColumn('items', 'book_maximum_days_in_advance');
UPDATE config SET conf_value = 202 WHERE conf_name = 'schema';
