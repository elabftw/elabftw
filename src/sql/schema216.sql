-- schema 216
ALTER TABLE users
  ADD COLUMN accent_color CHAR(7) DEFAULT NULL,
  ADD COLUMN accent_foreground CHAR(7) DEFAULT NULL;
