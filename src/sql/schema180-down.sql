CREATE TABLE `groups` (
  `id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_sysadmin` tinyint UNSIGNED NOT NULL,
  `is_admin` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
INSERT INTO `groups` (`id`, `name`, `is_sysadmin`, `is_admin`) VALUES
(1, 'Sysadmins', 1, '1'),
(2, 'Admins', 0, '1'),
(4, 'Users', 0, '0');

ALTER TABLE users2teams CHANGE COLUMN is_admin groups_id TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
UPDATE users2teams SET groups_id = IF(groups_id = 1, 2, 4);
ALTER TABLE users2teams DROP COLUMN is_archived;
-- ALTER TABLE users2teams ADD KEY `fk_users2teams_groups_id` (`groups_id`);
-- ALTER TABLE users2teams ADD CONSTRAINT `fk_users2teams_groups_id` FOREIGN KEY (`groups_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE teams DROP COLUMN users_canwrite_experiments_categories;
ALTER TABLE teams DROP COLUMN users_canwrite_experiments_status;
ALTER TABLE teams DROP COLUMN users_canwrite_resources_categories;
ALTER TABLE teams DROP COLUMN users_canwrite_resources_status;
INSERT INTO config (conf_name, conf_value) VALUES ('debug', '0');
ALTER TABLE users DROP COLUMN can_manage_users2teams;

UPDATE config SET conf_value = 179 WHERE conf_name = 'schema';
