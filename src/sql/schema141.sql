-- schema 141
-- Because of the use of filter_var with the now deprecated filter FILTER_SANITIZE_STRING
-- there are potentially columns in the database with html entities: encoded quotation mark (" -> &#34;) and apostrophe (' -> &#39;).
-- As we switch now from a "sanitize input" to a "encode output" strategy these need to be decoded to avoid double encoding
-- A temporary SQL procedure is used to avoid a lot of code duplication
-- A line in the procedure must not end with the delimiter ';' or it will not work. Hence, the inline comments (/**/) at the EOLs
DROP PROCEDURE IF EXISTS `update_column`;
CREATE PROCEDURE `update_column`(IN `table_name` CHAR(255), IN `column_name` CHAR(255))
MODIFIES SQL DATA
BEGIN
    -- decode entities
    SET @sql_text = concat('UPDATE ', table_name, ' SET ', column_name, ' = REPLACE(', column_name, ', "&#34;", ''"'');'); /**/
    PREPARE stmt FROM @sql_text; /**/
    EXECUTE stmt; /**/
    DEALLOCATE PREPARE stmt; /**/
    SET @sql_text = concat('UPDATE ', table_name, ' SET ', column_name, ' = REPLACE(', column_name, ', "&#39;", "''");'); /**/
    PREPARE stmt FROM @sql_text; /**/
    EXECUTE stmt; /**/
    DEALLOCATE PREPARE stmt; /**/
    -- Comments used to store line break elements (<br />) in the database
    -- but that will interfere with output encoding. So, they will be remove.
    -- nl2br will be used in the twig templates after escaping/encoding
    IF column_name = 'comment' THEN
        SET @sql_text = concat('UPDATE ', table_name, ' SET ', column_name, ' = REPLACE(', column_name, ', "<br />", "");'); /**/
        PREPARE stmt FROM @sql_text; /**/
        EXECUTE stmt; /**/
        DEALLOCATE PREPARE stmt; /**/
    END IF; /**/
END;
DROP PROCEDURE IF EXISTS `update_column_metadata`;
CREATE PROCEDURE `update_column_metadata`(IN `table_name` CHAR(255))
MODIFIES SQL DATA
BEGIN
    -- replace &#39; and &#34; with \" and '
    SET @sql_text = concat('UPDATE ', table_name, ' SET metadata = CONVERT(REPLACE(CONVERT(`metadata`, CHAR CHARACTER SET utf8mb4), "&#34;", ''\\\\"''), JSON);'); /**/
    PREPARE stmt FROM @sql_text; /**/
    EXECUTE stmt; /**/
    DEALLOCATE PREPARE stmt; /**/
    SET @sql_text = concat('UPDATE ', table_name, ' SET metadata = CONVERT(REPLACE(CONVERT(`metadata`, CHAR CHARACTER SET utf8mb4), "&#39;", "''"), JSON);'); /**/
    PREPARE stmt FROM @sql_text; /**/
    EXECUTE stmt; /**/
    DEALLOCATE PREPARE stmt; /**/
END;
CALL update_column('api_keys', 'name');
CALL update_column('experiments', 'title');
CALL update_column_metadata('experiments');
CALL update_column('experiments_categories', 'title');
CALL update_column('experiments_comments', 'comment');
CALL update_column('experiments_status', 'title');
CALL update_column('experiments_steps', 'body');
CALL update_column('experiments_templates', 'title');
CALL update_column_metadata('experiments_templates');
CALL update_column('experiments_templates_steps', 'body');
CALL update_column('items', 'title');
CALL update_column_metadata('items');
CALL update_column('items_comments', 'comment');
CALL update_column('items_status', 'title');
CALL update_column('items_steps', 'body');
CALL update_column('items_types', 'title');
CALL update_column_metadata('items_types');
CALL update_column('items_types_steps', 'body');
CALL update_column('tags', 'tag');
CALL update_column('teams', 'name');
CALL update_column('teams', 'link_name');
CALL update_column('team_events', 'title');
CALL update_column('team_groups', 'name');
CALL update_column('todolist', 'body');
CALL update_column('uploads', 'comment');
CALL update_column('users', 'firstname');
CALL update_column('users', 'lastname');
DROP PROCEDURE IF EXISTS `update_column`;
DROP PROCEDURE IF EXISTS `update_column_metadata`;
UPDATE config SET conf_value = 141 WHERE conf_name = 'schema';
