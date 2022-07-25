-- Schema 97
-- link experiments to experiments/items
-- Table structure for table `experiments2experiments`
CREATE TABLE `experiments2experiments` (
  `item_id` int UNSIGNED NOT NULL,
  `link_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
-- Indexes for table `experiments2experiments`
ALTER TABLE `experiments2experiments`
  ADD PRIMARY KEY (`item_id`,`link_id`),
  ADD KEY `fk_experiments2experiments_item_id` (`item_id`),
  ADD KEY `fk_experiments2experiments_link_id` (`link_id`);
-- Constraints for table `experiments2experiments`
ALTER TABLE `experiments2experiments`
  ADD CONSTRAINT `fk_experiments2experiments_item_id`
    FOREIGN KEY (`item_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments2experiments_link_id`
    FOREIGN KEY (`link_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;
-- Table structure for table `items2experiments`
CREATE TABLE `items2experiments` (
  `item_id` int UNSIGNED NOT NULL,
  `link_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
-- Indexes for table `items2experiments`
ALTER TABLE `items2experiments`
  ADD PRIMARY KEY (`item_id`,`link_id`),
  ADD KEY `fk_items2experiments_item_id` (`item_id`),
  ADD KEY `fk_items2experiments_link_id` (`link_id`);
-- Constraints for table `items2experiments`
ALTER TABLE `items2experiments`
  ADD CONSTRAINT `fk_items2experiments_item_id`
    FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items2experiments_link_id`
    FOREIGN KEY (`link_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;
UPDATE config SET conf_value = 97 WHERE conf_name = 'schema';
