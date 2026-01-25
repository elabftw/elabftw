-- schema 197
ALTER TABLE experiments
  ADD COLUMN canread_base  TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN canwrite_base TINYINT UNSIGNED NOT NULL DEFAULT 20;
ALTER TABLE experiments_templates
  ADD COLUMN canread_base  TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN canwrite_base TINYINT UNSIGNED NOT NULL DEFAULT 20;
ALTER TABLE items
  ADD COLUMN canread_base  TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN canbook_base  TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN canwrite_base TINYINT UNSIGNED NOT NULL DEFAULT 20;
ALTER TABLE items_types
  ADD COLUMN canread_base  TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN canbook_base  TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN canwrite_base TINYINT UNSIGNED NOT NULL DEFAULT 20;
ALTER TABLE users
  ADD COLUMN default_read_base  TINYINT UNSIGNED NOT NULL DEFAULT 30,
  ADD COLUMN default_write_base TINYINT UNSIGNED NOT NULL DEFAULT 20;

UPDATE experiments
SET
  canread_base  = CAST(JSON_EXTRACT(canread,  '$.base') AS UNSIGNED),
  canwrite_base = CAST(JSON_EXTRACT(canwrite, '$.base') AS UNSIGNED);
UPDATE experiments_templates
SET
  canread_base  = CAST(JSON_EXTRACT(canread,  '$.base') AS UNSIGNED),
  canwrite_base = CAST(JSON_EXTRACT(canwrite, '$.base') AS UNSIGNED);
UPDATE items
SET
  canread_base  = CAST(JSON_EXTRACT(canread,  '$.base') AS UNSIGNED),
  canbook_base  = CAST(JSON_EXTRACT(canbook,  '$.base') AS UNSIGNED),
  canwrite_base = CAST(JSON_EXTRACT(canwrite, '$.base') AS UNSIGNED);
-- here we use canread as canbook_base because items_types don't have canbook
UPDATE items_types
SET
  canread_base  = CAST(JSON_EXTRACT(canread,  '$.base') AS UNSIGNED),
  canbook_base  = CAST(JSON_EXTRACT(canread,  '$.base') AS UNSIGNED),
  canwrite_base = CAST(JSON_EXTRACT(canwrite, '$.base') AS UNSIGNED);

-- now remove the base from json
UPDATE experiments
SET
  canread  = JSON_REMOVE(canread,  '$.base'),
  canwrite = JSON_REMOVE(canwrite, '$.base');

UPDATE experiments_templates
SET
  canread  = JSON_REMOVE(canread,  '$.base'),
  canwrite = JSON_REMOVE(canwrite, '$.base');

UPDATE items
SET
  canread  = JSON_REMOVE(canread,  '$.base'),
  canbook  = JSON_REMOVE(canbook,  '$.base'),
  canwrite = JSON_REMOVE(canwrite, '$.base');

UPDATE items_types
SET
  canread  = JSON_REMOVE(canread,  '$.base'),
  canwrite = JSON_REMOVE(canwrite, '$.base');

CREATE INDEX idx_experiments_canread_base        ON experiments (canread_base);
CREATE INDEX idx_experiments_canwrite_base       ON experiments (canwrite_base);

CREATE INDEX idx_experiments_tmpl_canread_base   ON experiments_templates (canread_base);
CREATE INDEX idx_experiments_tmpl_canwrite_base  ON experiments_templates (canwrite_base);

CREATE INDEX idx_items_canread_base              ON items (canread_base);
CREATE INDEX idx_items_canbook_base              ON items (canbook_base);
CREATE INDEX idx_items_canwrite_base             ON items (canwrite_base);

CREATE INDEX idx_items_types_canread_base        ON items_types (canread_base);
CREATE INDEX idx_items_types_canbook_base        ON items_types (canbook_base);
CREATE INDEX idx_items_types_canwrite_base       ON items_types (canwrite_base);
