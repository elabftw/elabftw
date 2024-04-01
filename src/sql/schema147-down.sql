-- revert schema 147
ALTER TABLE users DROP COLUMN sig_pubkey;
ALTER TABLE users DROP COLUMN sig_privkey;
ALTER TABLE experiments_comments DROP COLUMN immutable;
ALTER TABLE items_comments DROP COLUMN immutable;
DROP TABLE experiments_request_actions;
DROP TABLE items_request_actions;
UPDATE config SET conf_value = 146 WHERE conf_name = 'schema';
