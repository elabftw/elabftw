-- Schema 55
START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_remote_user', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_firstname', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_lastname', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_email', '');
    INSERT INTO config (conf_name, conf_value) VALUES ('extauth_teams', '');

    UPDATE config SET conf_value = 55 WHERE conf_name = 'schema';
COMMIT;
