-- schema 143 - fix notifications for new version blocking the rest
UPDATE notifications SET send_email = 0 WHERE category = 70;
UPDATE config SET conf_value = 143 WHERE conf_name = 'schema';
