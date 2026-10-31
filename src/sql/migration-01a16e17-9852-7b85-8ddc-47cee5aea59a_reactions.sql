-- First-class reaction records, owned by an entry through typed links.
CREATE TABLE reactions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  scheme JSON NULL,
  rxn MEDIUMTEXT NOT NULL,
  preview_png MEDIUMTEXT NOT NULL,
  temperature_c DECIMAL(24,12) NULL,
  duration_s DECIMAL(24,12) NULL,
  pressure_pa DECIMAL(24,12) NULL,
  atmosphere VARCHAR(255) NOT NULL DEFAULT '',
  conditions_notes TEXT NOT NULL,
  reference_component_key CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
  yield_basis_component_key CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
  version INT UNSIGNED NOT NULL DEFAULT 1,
  state TINYINT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  modified_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by INT UNSIGNED NOT NULL,
  modified_by INT UNSIGNED NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE reaction_components (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  reaction_id INT UNSIGNED NOT NULL,
  component_key CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  ordering SMALLINT UNSIGNED NOT NULL,
  role VARCHAR(20) NOT NULL,
  name TEXT NOT NULL,
  molfile MEDIUMTEXT NOT NULL,
  smiles MEDIUMTEXT NOT NULL,
  inchi_key VARCHAR(27) NOT NULL DEFAULT '',
  molecular_formula VARCHAR(255) NOT NULL DEFAULT '',
  molecular_weight DECIMAL(24,12) NULL,
  compound_id INT UNSIGNED NULL,
  item_id INT UNSIGNED NULL,
  material_form VARCHAR(10) NOT NULL DEFAULT 'neat',
  purity_percent DECIMAL(24,12) NOT NULL DEFAULT 100,
  density_g_ml DECIMAL(24,12) NULL,
  concentration_mol_l DECIMAL(24,12) NULL,
  coefficient DECIMAL(24,12) NOT NULL DEFAULT 1,
  planned_basis VARCHAR(16) NOT NULL DEFAULT 'mass',
  planned_unit VARCHAR(8) NOT NULL DEFAULT 'g',
  planned_value DECIMAL(24,12) NULL,
  actual_basis VARCHAR(16) NOT NULL DEFAULT 'mass',
  actual_unit VARCHAR(8) NOT NULL DEFAULT 'g',
  actual_value DECIMAL(24,12) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_reaction_component_key (reaction_id, component_key),
  CONSTRAINT fk_reaction_components_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_reaction_components_compound FOREIGN KEY (compound_id) REFERENCES compounds(id) ON DELETE SET NULL,
  CONSTRAINT fk_reaction_components_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL
);

CREATE TABLE reaction_revisions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  reaction_id INT UNSIGNED NOT NULL,
  version INT UNSIGNED NOT NULL,
  snapshot JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_reaction_revision (reaction_id, version),
  CONSTRAINT fk_reaction_revisions_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE
);

CREATE TABLE reactions2experiments (
  reaction_id INT UNSIGNED NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  ordering INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (reaction_id),
  KEY idx_r2experiments_entity (entity_id, ordering),
  CONSTRAINT fk_r2experiments_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_r2experiments_entity FOREIGN KEY (entity_id) REFERENCES experiments(id) ON DELETE CASCADE
);

CREATE TABLE reactions2items (
  reaction_id INT UNSIGNED NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  ordering INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (reaction_id),
  KEY idx_r2items_entity (entity_id, ordering),
  CONSTRAINT fk_r2items_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_r2items_entity FOREIGN KEY (entity_id) REFERENCES items(id) ON DELETE CASCADE
);

CREATE TABLE reactions2experiments_templates (
  reaction_id INT UNSIGNED NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  ordering INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (reaction_id),
  KEY idx_r2experiments_templates_entity (entity_id, ordering),
  CONSTRAINT fk_r2experiments_templates_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_r2experiments_templates_entity FOREIGN KEY (entity_id) REFERENCES experiments_templates(id) ON DELETE CASCADE
);

CREATE TABLE reactions2items_types (
  reaction_id INT UNSIGNED NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  ordering INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (reaction_id),
  KEY idx_r2items_types_entity (entity_id, ordering),
  CONSTRAINT fk_r2items_types_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_r2items_types_entity FOREIGN KEY (entity_id) REFERENCES items_types(id) ON DELETE CASCADE
);
