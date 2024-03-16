-- revert schema 147
ALTER TABLE users DROP COLUMN sig_pubkey;
ALTER TABLE users DROP COLUMN sig_privkey;
UPDATE config SET conf_value = 146 WHERE conf_name = 'schema';
