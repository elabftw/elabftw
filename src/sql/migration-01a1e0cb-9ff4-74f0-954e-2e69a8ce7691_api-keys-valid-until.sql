-- migration 01a1e0cb-9ff4-74f0-954e-2e69a8ce7691

ALTER TABLE `api_keys`
  ADD COLUMN `valid_until` DATE NULL DEFAULT NULL AFTER `last_used_at`;

