-- revert schema 169
DROP TABLE IF EXISTS compounds_fingerprints;
DROP TABLE IF EXISTS compounds2experiments;
DROP TABLE IF EXISTS compounds2experiments_templates;
DROP TABLE IF EXISTS compounds2items;
DROP TABLE IF EXISTS compounds2items_types;
DROP TABLE IF EXISTS compounds;
DROP TABLE IF EXISTS containers2experiments;
DROP TABLE IF EXISTS containers2experiments_templates;
DROP TABLE IF EXISTS containers2items;
DROP TABLE IF EXISTS containers2items_types;
DROP TABLE IF EXISTS storage_units;
ALTER TABLE `tags` DROP INDEX `unique_tags_team_tag`;
UPDATE config SET conf_value = 168 WHERE conf_name = 'schema';
