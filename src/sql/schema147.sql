-- schema 147
ALTER TABLE users ADD sig_pubkey TEXT NULL DEFAULT NULL;
ALTER TABLE users ADD sig_privkey TEXT NULL DEFAULT NULL;
UPDATE config SET conf_value = 147 WHERE conf_name = 'schema';
