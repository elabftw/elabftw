-- migration 01a1d000-0000-7000-8000-e0fb06f883ea

INSERT IGNORE INTO config (conf_name, conf_value) VALUES ('inveniordm_host', ''), ('inveniordm_name', 'InvenioRDM');
ALTER TABLE `users` ADD COLUMN `inveniordm_token` VARCHAR(510) NOT NULL DEFAULT '';
