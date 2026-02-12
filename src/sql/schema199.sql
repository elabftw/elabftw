-- schema 199
-- fix idps_endpoints incorrect unique key for IdPs with same URL
-- we need to first create an index on idp column or it will refuse to drop compound idx
ALTER TABLE `idps_endpoints` ADD INDEX `idx_idps_endpoints_idp` (`idp`);
CALL DropIdx('idps_endpoints', 'uniq_idp_bdg_loc');
ALTER TABLE `idps_endpoints`
  ADD UNIQUE KEY `uniq_idp_bdg_loc` (`idp`, `binding`, `location`, `is_slo`);
-- cleanup that index that we don't need now
ALTER TABLE `idps_endpoints` DROP INDEX `idx_idps_endpoints_idp`;
