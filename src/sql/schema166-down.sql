-- revert schema 166
-- nothing to do here; changes are done in schema151-down.sql as the backend changes happened between 151 and 152
UPDATE config SET conf_value = 165 WHERE conf_name = 'schema';
