-- revert schema 177
DROP TABLE IF EXISTS experiments_templates_comments;
UPDATE config SET conf_value = 176 WHERE conf_name = 'schema';
