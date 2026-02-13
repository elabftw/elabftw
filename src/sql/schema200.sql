-- schema 200
ALTER TABLE teams ADD COLUMN users_canwrite_experiments TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE teams ADD COLUMN users_canwrite_experiments_templates TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE teams ADD COLUMN users_canwrite_resources TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE teams ADD COLUMN users_canwrite_resources_templates TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;
