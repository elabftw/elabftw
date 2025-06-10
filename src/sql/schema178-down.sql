-- revert schema 178
DROP TABLE IF EXISTS experiments_templates_comments;
UPDATE config SET conf_value = 177 WHERE conf_name = 'schema';
