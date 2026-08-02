-- schema 217
UPDATE users
SET token = NULL,
    token_created_at = NULL
WHERE token IS NOT NULL;
UPDATE users
SET token = NULL,
    token_created_at = NULL
WHERE token IS NOT NULL
   OR token_created_at IS NOT NULL;
