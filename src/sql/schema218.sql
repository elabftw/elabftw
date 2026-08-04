-- schema 218
ALTER TABLE users
  ADD COLUMN primary_color CHAR(7) DEFAULT NULL,
  ADD COLUMN primary_foreground CHAR(7) DEFAULT NULL;
