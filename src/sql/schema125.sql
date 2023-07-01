-- schema 125
INSERT INTO config (conf_name, conf_value) VALUES ('legal_notice', NULL);
INSERT INTO config (conf_name, conf_value) VALUES ('legal_notice_name', 'Legal notice');
INSERT INTO config (conf_name, conf_value) VALUES ('privacy_policy_name', 'Privacy policy');
INSERT INTO config (conf_name, conf_value) VALUES ('terms_of_service_name', 'Terms of service');
INSERT INTO config (conf_name, conf_value) VALUES ('a11y_statement_name', 'Accessibility statement');
UPDATE config SET conf_value = 125 WHERE conf_name = 'schema';
