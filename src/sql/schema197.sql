-- schema 197
ALTER TABLE experiments
  ADD COLUMN canread_base  TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN canwrite_base TINYINT UNSIGNED NOT NULL DEFAULT 20;
ALTER TABLE experiments_templates
  ADD COLUMN canread_base  TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN canwrite_base TINYINT UNSIGNED NOT NULL DEFAULT 20,
  ADD COLUMN canread_target_base TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN canwrite_target_base TINYINT UNSIGNED NOT NULL DEFAULT 20;
ALTER TABLE items
  ADD COLUMN canread_base  TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN canbook_base  TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN canwrite_base TINYINT UNSIGNED NOT NULL DEFAULT 20;
ALTER TABLE items_types
  ADD COLUMN canread_base  TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN canbook JSON NOT NULL,
  ADD COLUMN canbook_base  TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN canwrite_base TINYINT UNSIGNED NOT NULL DEFAULT 20,
  ADD COLUMN canread_target_base TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN canwrite_target_base TINYINT UNSIGNED NOT NULL DEFAULT 20;
-- backfill canbook for existing rows (sync with canread semantics)
UPDATE items_types SET canbook = canread WHERE canbook IS NULL;

ALTER TABLE users
  ADD COLUMN default_read_base  TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN default_write_base TINYINT UNSIGNED NOT NULL DEFAULT 20;

UPDATE experiments
SET
  canread_base  = COALESCE(CAST(JSON_EXTRACT(canread,  '$.base') AS UNSIGNED), 30),
  canwrite_base = COALESCE(CAST(JSON_EXTRACT(canwrite, '$.base') AS UNSIGNED), 20),
  modified_at = modified_at;
UPDATE experiments_templates
SET
  canread_base  = COALESCE(CAST(JSON_EXTRACT(canread,  '$.base') AS UNSIGNED), 30),
  canwrite_base = COALESCE(CAST(JSON_EXTRACT(canwrite, '$.base') AS UNSIGNED), 20),
  canread_target_base = COALESCE(CAST(JSON_EXTRACT(canread_target, '$.base') AS UNSIGNED), 30),
  canwrite_target_base = COALESCE(CAST(JSON_EXTRACT(canwrite_target, '$.base') AS UNSIGNED), 20),
  modified_at = modified_at;
UPDATE items
SET
  canread_base  = COALESCE(CAST(JSON_EXTRACT(canread,  '$.base') AS UNSIGNED), 30),
  canbook_base  = COALESCE(CAST(JSON_EXTRACT(canbook,  '$.base') AS UNSIGNED), 30),
  canwrite_base = COALESCE(CAST(JSON_EXTRACT(canwrite, '$.base') AS UNSIGNED), 20),
  modified_at = modified_at;
-- here we use canread as canbook_base because items_types don't have canbook
UPDATE items_types
SET
  canread_base  = COALESCE(CAST(JSON_EXTRACT(canread,  '$.base') AS UNSIGNED), 30),
  canbook_base  = COALESCE(CAST(JSON_EXTRACT(canbook,  '$.base') AS UNSIGNED), 30),
  canwrite_base = COALESCE(CAST(JSON_EXTRACT(canwrite, '$.base') AS UNSIGNED), 20),
  canread_target_base = COALESCE(CAST(JSON_EXTRACT(canread_target, '$.base') AS UNSIGNED), 30),
  canwrite_target_base = COALESCE(CAST(JSON_EXTRACT(canwrite_target, '$.base') AS UNSIGNED), 20),
  modified_at = modified_at;

-- for users defaults
UPDATE users
SET
  default_read_base  = COALESCE(CAST(JSON_EXTRACT(default_read,  '$.base') AS UNSIGNED), 30),
  default_write_base = COALESCE(CAST(JSON_EXTRACT(default_write, '$.base') AS UNSIGNED), 20);

-- now remove the base from json
UPDATE experiments
SET
  canread  = JSON_REMOVE(canread,  '$.base'),
  canwrite = JSON_REMOVE(canwrite, '$.base'),
  modified_at = modified_at;

UPDATE experiments_templates
SET
  canread  = JSON_REMOVE(canread,  '$.base'),
  canwrite = JSON_REMOVE(canwrite, '$.base'),
  canread_target = JSON_REMOVE(canread_target, '$.base'),
  canwrite_target = JSON_REMOVE(canwrite_target, '$.base'),
  modified_at = modified_at;

UPDATE items
SET
  canread  = JSON_REMOVE(canread,  '$.base'),
  canbook  = JSON_REMOVE(canbook,  '$.base'),
  canwrite = JSON_REMOVE(canwrite, '$.base'),
  modified_at = modified_at;

UPDATE items_types
SET
  canread  = JSON_REMOVE(canread,  '$.base'),
  canwrite = JSON_REMOVE(canwrite, '$.base'),
  canread_target = JSON_REMOVE(canread_target, '$.base'),
  canwrite_target = JSON_REMOVE(canwrite_target, '$.base'),
  modified_at = modified_at;

UPDATE users
SET
  default_read = JSON_REMOVE(default_read,  '$.base'),
  default_write = JSON_REMOVE(default_write, '$.base');

CREATE INDEX idx_experiments_canread_base        ON experiments (canread_base);

CREATE INDEX idx_experiments_tmpl_canread_base   ON experiments_templates (canread_base);

CREATE INDEX idx_items_canread_base              ON items (canread_base);
CREATE INDEX idx_items_canbook_base              ON items (canbook_base);

CREATE INDEX idx_items_types_canread_base        ON items_types (canread_base);
CREATE INDEX idx_items_types_canbook_base        ON items_types (canbook_base);
