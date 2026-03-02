-- revert schema 199
-- Revert uniq_idp_bdg_loc back to (idp, binding, location)
ALTER TABLE `idps_endpoints` ADD INDEX `idx_idps_endpoints_idp` (`idp`);
CALL DropIdx('idps_endpoints', 'uniq_idp_bdg_loc');
ALTER TABLE `idps_endpoints`
  ADD UNIQUE KEY `uniq_idp_bdg_loc` (`idp`, `binding`, `location`);
CALL DropIdx('idps_endpoints', 'idx_idps_endpoints_idp');
UPDATE config SET conf_value = 198 WHERE conf_name = 'schema';
