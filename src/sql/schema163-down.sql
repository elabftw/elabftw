-- revert schema 163
DELETE FROM config WHERE conf_name = 'email_send_grouped';
UPDATE config SET conf_value = 162 WHERE conf_name = 'schema';
