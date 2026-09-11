-- revert schema 225
DROP TABLE IF EXISTS `webhooks_queue`;
DROP TABLE IF EXISTS `webhooks`;

UPDATE config SET conf_value = 224 WHERE conf_name = 'schema';
