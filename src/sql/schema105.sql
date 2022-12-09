-- schema 105
-- EXPERIMENTS
CREATE TABLE IF NOT EXISTS `experiments_changelog` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_id` INT(10) UNSIGNED NOT NULL,
    `users_id` INT(10) UNSIGNED NOT NULL ,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `target` VARCHAR(255) NOT NULL,
    `content` text NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

ALTER TABLE `experiments_changelog`
  ADD CONSTRAINT `fk_experiments_changelog2experiments_id` FOREIGN KEY (`entity_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_changelog2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ITEMS
CREATE TABLE IF NOT EXISTS `items_changelog` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_id` INT(10) UNSIGNED NOT NULL,
    `users_id` INT(10) UNSIGNED NOT NULL ,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `target` VARCHAR(255) NOT NULL,
    `content` text NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

ALTER TABLE `items_changelog`
  ADD CONSTRAINT `fk_items_changelog2items_id` FOREIGN KEY (`entity_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_changelog2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

-- EXPERIMENTS TEMPLATES
CREATE TABLE IF NOT EXISTS `experiments_templates_changelog` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_id` INT(10) UNSIGNED NOT NULL,
    `users_id` INT(10) UNSIGNED NOT NULL ,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `target` VARCHAR(255) NOT NULL,
    `content` text NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

ALTER TABLE `experiments_templates_changelog`
  ADD CONSTRAINT `fk_experiments_templates_changelog2experiments_templates_id` FOREIGN KEY (`entity_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_templates_changelog2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ITEMS TYPES
CREATE TABLE IF NOT EXISTS `items_types_changelog` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_id` INT(10) UNSIGNED NOT NULL,
    `users_id` INT(10) UNSIGNED NOT NULL ,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `target` VARCHAR(255) NOT NULL,
    `content` text NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

ALTER TABLE `items_types_changelog`
  ADD CONSTRAINT `fk_items_types_changelog2items_types_id` FOREIGN KEY (`entity_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_types_changelog2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE config SET conf_value = 105 WHERE conf_name = 'schema';
