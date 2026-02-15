-- schema 203
-- add OIDC identity providers table
CREATE TABLE IF NOT EXISTS `idps_oidc` (
  `id` int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `issuer` varchar(512) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `client_id` varchar(255) NOT NULL,
  `client_secret` text NOT NULL,
  `authorization_endpoint` varchar(512) NOT NULL,
  `token_endpoint` varchar(512) NOT NULL,
  `userinfo_endpoint` varchar(512) NOT NULL,
  `end_session_endpoint` varchar(512) DEFAULT NULL,
  `jwks_uri` varchar(512) DEFAULT NULL,
  `scope` varchar(255) NOT NULL DEFAULT 'openid email profile',
  `email_claim` varchar(100) NOT NULL DEFAULT 'email',
  `fname_claim` varchar(100) NOT NULL DEFAULT 'given_name',
  `lname_claim` varchar(100) NOT NULL DEFAULT 'family_name',
  `team_claim` varchar(100) DEFAULT NULL,
  `orgid_claim` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_idps_oidc_enabled` (`enabled`),
  KEY `idx_idps_oidc_issuer` (`issuer`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- add OIDC config entries
INSERT IGNORE INTO config (conf_name, conf_value) VALUES
  ('oidc_toggle', '0'),
  ('oidc_debug', '0'),
  ('oidc_team_create', '1'),
  ('oidc_team_default', '-1'),
  ('oidc_user_default', '1'),
  ('oidc_fallback_orgid', '0'),
  ('oidc_sync_teams', '0'),
  ('oidc_sync_email_idp', '0');