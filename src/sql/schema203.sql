-- schema 203
update items_types set canbook = canread where json_unquote(canbook) = 'null';
