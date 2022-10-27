-- Schema 101
ALTER TABLE `teams` ADD `common_template_md` text;
UPDATE config SET conf_value = 101 WHERE conf_name = 'schema';
