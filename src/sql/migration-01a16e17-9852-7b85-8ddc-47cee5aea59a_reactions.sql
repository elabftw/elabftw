-- First-class reaction records, owned by an entry through typed links.
-- IF NOT EXISTS keeps this migration safe to apply again with --force.
CREATE TABLE IF NOT EXISTS reactions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  scheme JSON NULL,
  rxn MEDIUMTEXT NOT NULL,
  -- derived from the components on save: reactants>agents>products
  reaction_smiles MEDIUMTEXT NOT NULL,
  -- derived on save: SHA-256 of the sorted reactant and product InChIKeys, to find the same transformation
  reaction_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
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
  PRIMARY KEY (id),
  KEY idx_reactions_reaction_key (reaction_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS reaction_components (
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
  KEY idx_reaction_components_inchi_key (inchi_key),
  CONSTRAINT fk_reaction_components_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_reaction_components_compound FOREIGN KEY (compound_id) REFERENCES compounds(id) ON DELETE SET NULL,
  CONSTRAINT fk_reaction_components_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- Open Babel FP2 fingerprints of the components, same layout as compounds_fingerprints, for substructure search
CREATE TABLE IF NOT EXISTS reaction_component_fingerprints (
  component_id INT UNSIGNED NOT NULL,
  fp0 INT UNSIGNED NOT NULL,
  fp1 INT UNSIGNED NOT NULL,
  fp2 INT UNSIGNED NOT NULL,
  fp3 INT UNSIGNED NOT NULL,
  fp4 INT UNSIGNED NOT NULL,
  fp5 INT UNSIGNED NOT NULL,
  fp6 INT UNSIGNED NOT NULL,
  fp7 INT UNSIGNED NOT NULL,
  fp8 INT UNSIGNED NOT NULL,
  fp9 INT UNSIGNED NOT NULL,
  fp10 INT UNSIGNED NOT NULL,
  fp11 INT UNSIGNED NOT NULL,
  fp12 INT UNSIGNED NOT NULL,
  fp13 INT UNSIGNED NOT NULL,
  fp14 INT UNSIGNED NOT NULL,
  fp15 INT UNSIGNED NOT NULL,
  fp16 INT UNSIGNED NOT NULL,
  fp17 INT UNSIGNED NOT NULL,
  fp18 INT UNSIGNED NOT NULL,
  fp19 INT UNSIGNED NOT NULL,
  fp20 INT UNSIGNED NOT NULL,
  fp21 INT UNSIGNED NOT NULL,
  fp22 INT UNSIGNED NOT NULL,
  fp23 INT UNSIGNED NOT NULL,
  fp24 INT UNSIGNED NOT NULL,
  fp25 INT UNSIGNED NOT NULL,
  fp26 INT UNSIGNED NOT NULL,
  fp27 INT UNSIGNED NOT NULL,
  fp28 INT UNSIGNED NOT NULL,
  fp29 INT UNSIGNED NOT NULL,
  fp30 INT UNSIGNED NOT NULL,
  fp31 INT UNSIGNED NOT NULL,
  PRIMARY KEY (component_id),
  CONSTRAINT fk_reaction_component_fingerprints_component FOREIGN KEY (component_id) REFERENCES reaction_components(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS reaction_revisions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  reaction_id INT UNSIGNED NOT NULL,
  version INT UNSIGNED NOT NULL,
  snapshot JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_reaction_revision (reaction_id, version),
  CONSTRAINT fk_reaction_revisions_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS reactions2experiments (
  reaction_id INT UNSIGNED NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  ordering INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (reaction_id),
  KEY idx_r2experiments_entity (entity_id, ordering),
  CONSTRAINT fk_r2experiments_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_r2experiments_entity FOREIGN KEY (entity_id) REFERENCES experiments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS reactions2items (
  reaction_id INT UNSIGNED NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  ordering INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (reaction_id),
  KEY idx_r2items_entity (entity_id, ordering),
  CONSTRAINT fk_r2items_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_r2items_entity FOREIGN KEY (entity_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS reactions2experiments_templates (
  reaction_id INT UNSIGNED NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  ordering INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (reaction_id),
  KEY idx_r2experiments_templates_entity (entity_id, ordering),
  CONSTRAINT fk_r2experiments_templates_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_r2experiments_templates_entity FOREIGN KEY (entity_id) REFERENCES experiments_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS reactions2items_types (
  reaction_id INT UNSIGNED NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  ordering INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (reaction_id),
  KEY idx_r2items_types_entity (entity_id, ordering),
  CONSTRAINT fk_r2items_types_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_r2items_types_entity FOREIGN KEY (entity_id) REFERENCES items_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
