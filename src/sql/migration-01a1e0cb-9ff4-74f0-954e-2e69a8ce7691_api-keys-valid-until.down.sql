-- revert migration 01a1e0cb-9ff4-74f0-954e-2e69a8ce7691

CALL DropColumn('api_keys', 'valid_until');

