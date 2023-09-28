-- revert schema 125
DELETE FROM config WHERE conf_name = 'legal_notice';
DELETE FROM config WHERE conf_name = 'legal_notice_name';
DELETE FROM config WHERE conf_name = 'privacy_policy_name';
DELETE FROM config WHERE conf_name = 'terms_of_service_name';
DELETE FROM config WHERE conf_name = 'a11y_statement_name';
UPDATE config SET conf_value = 124 WHERE conf_name = 'schema';
