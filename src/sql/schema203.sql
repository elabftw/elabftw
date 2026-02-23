-- schema 203
update items_types set canbook = canread, modified_at = modified_at where json_unquote(canbook) = 'null';
