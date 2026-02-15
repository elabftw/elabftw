-- revert 203
-- drop OIDC identity providers table
DROP TABLE IF EXISTS `idps_oidc`;

-- remove OIDC config entries
DELETE FROM config WHERE conf_name IN (
  'oidc_toggle',
  'oidc_debug',
  'oidc_team_create',
  'oidc_team_default',
  'oidc_user_default',
  'oidc_fallback_orgid',
  'oidc_sync_teams',
  'oidc_sync_email_idp'
);