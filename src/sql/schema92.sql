-- Schema 92
-- drop skype, add orcid
UPDATE `users` SET `skype` = NULL;
ALTER TABLE `users` CHANGE `skype` `orcid` VARCHAR(19) NULL DEFAULT NULL;
UPDATE config SET conf_value = 92 WHERE conf_name = 'schema';
