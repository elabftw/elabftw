-- revert schema 155
ALTER TABLE `items_types`
  DROP COLUMN `locked`,
  DROP COLUMN `lockedby`,
  DROP COLUMN `locked_at`;
UPDATE config SET conf_value = 154 WHERE conf_name = 'schema';
