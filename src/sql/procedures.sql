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
    IN IdxName   VARCHAR(64)
)
MODIFIES SQL DATA
BEGIN
  IF EXISTS (
    SELECT 1
      FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME        = tblName
       AND CONSTRAINT_NAME   = IdxName
       AND CONSTRAINT_TYPE   = 'INDEX'
  ) THEN
    SET @ddl = CONCAT(
      'ALTER TABLE `', tblName,
      '` DROP INDEX`', fkName, '`'
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
