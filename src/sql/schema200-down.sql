-- revert schema 200
CALL DropColumn('items', 'book_price_notax');
CALL DropColumn('items', 'book_price_tax');
CALL DropColumn('items', 'book_price_currency');
UPDATE config SET conf_value = 199 WHERE conf_name = 'schema';
