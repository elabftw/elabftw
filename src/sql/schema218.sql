-- schema 218
CALL DropColumn('users', 'enforce_exclusive_edit_mode');
ALTER TABLE users
  ADD COLUMN primary_bg CHAR(6) DEFAULT NULL,
  ADD COLUMN primary_fg CHAR(6) DEFAULT NULL;
