-- schema 149
-- Table structure for table `experiments_templates2experiments`
CREATE TABLE `experiments_templates2experiments` (
  `item_id` int UNSIGNED NOT NULL,
  `link_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`item_id`,`link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
-- Indexes for table `experiments_templates2experiments`
ALTER TABLE `experiments_templates2experiments`
  ADD KEY `fk_experiments_templates2experiments_item_id` (`item_id`),
  ADD KEY `fk_experiments_templates2experiments_link_id` (`link_id`);
-- Constraints for table `experiments_templates2experiments`
ALTER TABLE `experiments_templates2experiments`
  ADD CONSTRAINT `fk_experiments_templates2experiments_item_id`
    FOREIGN KEY (`item_id`) REFERENCES `experiments_templates` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_templates2experiments_link_id`
    FOREIGN KEY (`link_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;
UPDATE config SET conf_value = 149 WHERE conf_name = 'schema';
