-- DROP FOREIGN KEY PROCEDURE
DROP PROCEDURE IF EXISTS `DropFK`;
CREATE PROCEDURE `DropFK`(
    IN tblName  VARCHAR(64),
    IN fkName   VARCHAR(64)
)
MODIFIES SQL DATA
BEGIN
  IF EXISTS (
    SELECT 1
      FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME        = tblName
       AND CONSTRAINT_NAME   = fkName
       AND CONSTRAINT_TYPE   = 'FOREIGN KEY'
  ) THEN
    SET @ddl = CONCAT(
      'ALTER TABLE `', tblName,
      '` DROP FOREIGN KEY `', fkName, '`'
    ); /**/
    PREPARE stmt FROM @ddl; /**/
    EXECUTE stmt; /**/
    DEALLOCATE PREPARE stmt; /**/
  END IF; /**/
END;
-- END DROP FOREIGN KEY PROCEDURE

-- DROP INDEX PROCEDURE
DROP PROCEDURE IF EXISTS `DropIdx`;
CREATE PROCEDURE `DropIdx`(
    IN tblName  VARCHAR(64),
    IN idxName   VARCHAR(64)
)
MODIFIES SQL DATA
BEGIN
  IF EXISTS (
    SELECT 1
      FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME        = tblName
       AND INDEX_NAME   = idxName
  ) THEN
    SET @ddl = CONCAT(
      'ALTER TABLE `', tblName,
      '` DROP INDEX `', idxName, '`'
    ); /**/
    PREPARE stmt FROM @ddl; /**/
    EXECUTE stmt; /**/
    DEALLOCATE PREPARE stmt; /**/
  END IF; /**/
END;
-- END DROP INDEX PROCEDURE

-- DROP COLUMN PROCEDURE
-- remove old proc if present
DROP PROCEDURE IF EXISTS `DropColumn`;

-- create the new proc
CREATE PROCEDURE `DropColumn`(
    IN tblName  VARCHAR(64),
    IN colName  VARCHAR(64)
)
MODIFIES SQL DATA
BEGIN
  -- only run if the column actually exists in this schema
  IF EXISTS (
    SELECT 1
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = tblName
       AND COLUMN_NAME  = colName
  ) THEN
    SET @ddl = CONCAT(
      'ALTER TABLE `', tblName,
      '` DROP COLUMN `', colName, '`'
    ); /**/
    PREPARE stmt FROM @ddl; /**/
    EXECUTE stmt; /**/
    DEALLOCATE PREPARE stmt; /**/
  END IF; /**/
END;
-- END DROP COLUMN PROCEDURE

-- DROP FK IF EXISTS (second procedure when we don't know the name)
DROP PROCEDURE IF EXISTS `drop_fk_if_exists`;
CREATE PROCEDURE drop_fk_if_exists(
    IN tableName VARCHAR(64),
    IN colName VARCHAR(64)
)
MODIFIES SQL DATA
BEGIN
    DECLARE fk_name VARCHAR(64); /**/

    -- Find the foreign key constraint name for the given table and column
    SELECT CONSTRAINT_NAME INTO fk_name
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = tableName
      AND COLUMN_NAME = colName
      AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1; /**/

    -- If a foreign key is found, drop it
    IF fk_name IS NOT NULL THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` DROP FOREIGN KEY `', fk_name, '`;'); /**/
        PREPARE stmt FROM @sql; /**/
        EXECUTE stmt; /**/
        DEALLOCATE PREPARE stmt; /**/
    END IF; /**/
END;
-- END DROP FK IF EXISTS (second procedure when we don't know the name)
