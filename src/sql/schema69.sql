-- Schema 69
START TRANSACTION;
    ALTER TABLE `favtags2users` ADD `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);
    UPDATE config SET conf_value = 69 WHERE conf_name = 'schema';
COMMIT;
