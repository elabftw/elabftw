-- Schema 51
START TRANSACTION;
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_nameidencrypted', 0);
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_authnrequestssigned', 0);
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_logoutrequestsigned', 0);
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_logoutresponsesigned', 0);
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_signmetadata', 0);
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_wantmessagessigned', 0);
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_wantassertionsencrypted', 0);
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_wantassertionssigned', 0);
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_wantnameid', 1);
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_wantnameidencrypted', 0);
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_wantxmlvalidation', 1);
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_relaxdestinationvalidation', 0);
    INSERT INTO config (conf_name, conf_value) VALUES ('saml_lowercaseurlencoding', 0);
    UPDATE config SET conf_value = 51 WHERE conf_name = 'schema';
COMMIT;
