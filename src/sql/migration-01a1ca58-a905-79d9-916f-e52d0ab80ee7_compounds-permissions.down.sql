-- revert migration 01a1ca58-a905-79d9-916f-e52d0ab80ee7

ALTER TABLE `compounds`
  DROP COLUMN `canwrite`,
  DROP COLUMN `canread`,
  DROP COLUMN `canwrite_base`,
  DROP COLUMN `canread_base`;
