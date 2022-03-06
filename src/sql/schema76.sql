-- Schema 76
-- drop sendmail stuff
START TRANSACTION;
    DELETE FROM `config` WHERE `conf_name` = 'sendmail_path';
    DELETE FROM `config` WHERE `conf_name` = 'mail_method';
    UPDATE config SET conf_value = 76 WHERE conf_name = 'schema';
COMMIT;
