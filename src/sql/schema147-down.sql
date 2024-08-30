-- revert schema 147
DELETE FROM `config`
WHERE `conf_name` = 'onboarding_email_active'
  OR `conf_name` = 'onboarding_email_subject'
  OR `conf_name` = 'onboarding_email_body'
  OR `conf_name` = 'onboarding_email_different_for_admins'
  OR `conf_name` = 'onboarding_email_admins_subject'
  OR `conf_name` = 'onboarding_email_admins_body';
ALTER TABLE `teams`
  DROP `onboarding_email_active`,
  DROP `onboarding_email_subject`,
  DROP `onboarding_email_body`;
UPDATE config SET conf_value = 146 WHERE conf_name = 'schema';
