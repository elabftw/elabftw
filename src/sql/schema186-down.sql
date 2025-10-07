-- revert schema 186
-- no need to revert the compounds.name to varchar
CALL DropIdx('compounds', 'idx_compounds_name');
UPDATE config SET conf_value = 185 WHERE conf_name = 'schema';
