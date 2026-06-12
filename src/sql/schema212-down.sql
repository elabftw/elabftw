-- revert schema 212
DROP TABLE IF EXISTS `teams2rors`;
DROP TABLE IF EXISTS `instance2rors`;

UPDATE config SET conf_value = 211 WHERE conf_name = 'schema';
