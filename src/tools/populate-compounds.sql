-- this script populates compounds table with many rows
-- copy it in docker container, execute it from mysql container
-- docker cp src/tools/populate-compounds.sql mysql:/c.sql
-- elabctl mysql
-- source /c.sql;

SET @created_by_userid := 1;
SET @@cte_max_recursion_depth = 40000;
SET @team_id := 1;
SET @how_many := 40000;

INSERT INTO compounds (
    created_by,
    modified_by,
    userid,
    team,
    state,
    name,
    molecular_formula,
    molecular_weight,
    cas_number,
    smiles,
    inchi,
    inchi_key,
    iupac_name,
    pubchem_cid
)
WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1
    FROM seq
    WHERE n < @how_many
)
SELECT
    @created_by_userid AS created_by,
    @created_by_userid AS modified_by,
    @created_by_userid AS userid,
    @team_id AS team,
    1 AS state,
    CONCAT('Fake Compound ', LPAD(n, 6, '0')) AS name,
    CONCAT('C', 5 + MOD(n, 30), 'H', 8 + MOD(n, 50), 'N', MOD(n, 4), 'O', MOD(n, 8)) AS molecular_formula,
    12 as molecular_weight,
    CONCAT('FAKE-', LPAD(n, 6, '0')) AS cas_number,
    CASE MOD(n, 6)
        WHEN 0 THEN 'CCO'
        WHEN 1 THEN 'CCN'
        WHEN 2 THEN 'CC(=O)O'
        WHEN 3 THEN 'c1ccccc1'
        WHEN 4 THEN 'CCOC(=O)C'
        ELSE 'CC(C)O'
    END AS smiles,
    CONCAT('InChI=1S/FAKE-', LPAD(n, 6, '0')) AS inchi,
    CONCAT('FAKEINCHIKEY', LPAD(n, 13, '0')) AS inchi_key,
    CONCAT('fake compound ', LPAD(n, 6, '0')) AS iupac_name,
    900000000 + n AS pubchem_cid
FROM seq;
