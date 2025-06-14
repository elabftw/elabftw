-- revert schema 179
DROP TABLE IF EXISTS experiments_templates_comments;
UPDATE config SET conf_value = 178 WHERE conf_name = 'schema';
