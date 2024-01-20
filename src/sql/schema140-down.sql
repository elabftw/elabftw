-- revert schema 140
-- A temporary SQL procedure is used to avoid a lot of code duplication
-- A line in the procedure must not end with the delimiter ';' or it will not work. Hence the inline comments (/**/) at the ends
DROP PROCEDURE IF EXISTS `update_column`;
CREATE PROCEDURE `update_column`(IN `table_name` CHAR(255), IN `column_name` CHAR(255))
MODIFIES SQL DATA
BEGIN
    -- encode_entities
    SET @sql_text = concat('UPDATE ', table_name, ' SET ', column_name, ' = REPLACE(', column_name, ', ''"'', "&#34;");'); /**/
    PREPARE stmt FROM @sql_text; /**/
    EXECUTE stmt; /**/
    DEALLOCATE PREPARE stmt; /**/
    SET @sql_text = concat('UPDATE ', table_name, ' SET ', column_name, ' = REPLACE(', column_name, ', "''", "&#39;");'); /**/
    PREPARE stmt FROM @sql_text; /**/
    EXECUTE stmt; /**/
    DEALLOCATE PREPARE stmt; /**/
    SET @sql_text = concat('UPDATE ', table_name, ' SET ', column_name, ' = REPLACE(', column_name, ', "<", "");'); /**/
    PREPARE stmt FROM @sql_text; /**/
    EXECUTE stmt; /**/
    DEALLOCATE PREPARE stmt; /**/
    SET @sql_text = concat('UPDATE ', table_name, ' SET ', column_name, ' = REPLACE(', column_name, ', ">", "");'); /**/
    PREPARE stmt FROM @sql_text; /**/
    EXECUTE stmt; /**/
    DEALLOCATE PREPARE stmt; /**/
    -- add back the line break elements to comment columns like PHP's nl2br() does
    IF column_name = 'comment' THEN
        SET @sql_text = concat('UPDATE ', table_name, ' SET ', column_name, ' = REGEXP_REPLACE(', column_name, ', "(\r\n|\n\r|\n|\r)", "<br />$1", 1, 0, "m");'); /**/
        PREPARE stmt FROM @sql_text; /**/
        EXECUTE stmt; /**/
        DEALLOCATE PREPARE stmt; /**/
    END IF; /**/
END;
CALL update_column('api_keys', 'name');
CALL update_column('experiments', 'title');
CALL update_column('experiments_categories', 'title');
CALL update_column('experiments_comments', 'comment');
CALL update_column('experiments_status', 'title');
CALL update_column('experiments_steps', 'body');
CALL update_column('experiments_templates', 'title');
CALL update_column('experiments_templates_steps', 'body');
CALL update_column('items', 'title');
CALL update_column('items_comments', 'comment');
CALL update_column('items_status', 'title');
CALL update_column('items_steps', 'body');
CALL update_column('items_types', 'title');
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
UPDATE config SET conf_value = 139 WHERE conf_name = 'schema';
