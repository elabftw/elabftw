-- schema 217
UPDATE users
SET token = NULL,
    token_created_at = NULL
WHERE token IS NOT NULL
   OR token_created_at IS NOT NULL;
ALTER TABLE users
    MODIFY token CHAR(32)
        CHARACTER SET ascii
        COLLATE ascii_bin
        NULL,
    ADD UNIQUE INDEX idx_users_token (token);
