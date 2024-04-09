-- schema 147
INSERT INTO `config` (`conf_name`, `conf_value`) VALUES
  ('onboarding_email_active', '0'),
  ('onboarding_email_subject', NULL),
  ('onboarding_email_body', NULL),
  ('onboarding_email_different_for_admins', '0'),
  ('onboarding_email_admins_subject', NULL),
  ('onboarding_email_admins_body', NULL);
ALTER TABLE `teams`
  ADD `onboarding_email_active` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD `onboarding_email_subject` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  ADD `onboarding_email_body` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL;
UPDATE config SET conf_value = 147 WHERE conf_name = 'schema';
