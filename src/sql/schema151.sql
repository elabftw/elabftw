-- schema 151
--
-- Table structure for table `exports`
--

CREATE TABLE `exports` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `requester_userid` int UNSIGNED NOT NULL,
  `state` tinyint UNSIGNED NOT NULL DEFAULT 4,
  `long_name` varchar(255) DEFAULT NULL,
  `filesize` int UNSIGNED DEFAULT NULL,
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `real_name` varchar(255) DEFAULT NULL,
  `team` int UNSIGNED NOT NULL,
  `changelog` tinyint NOT NULL DEFAULT 0,
  `pdfa` tinyint NOT NULL DEFAULT 0,
  `json` tinyint NOT NULL DEFAULT 0,
  `hash` char(64) DEFAULT NULL,
  `hash_algo` varchar(255) DEFAULT NULL,
  `experiments` tinyint NOT NULL DEFAULT 1,
  `items` tinyint NOT NULL DEFAULT 0,
  `experiments_templates` tinyint NOT NULL DEFAULT 0,
  `items_types` tinyint NOT NULL DEFAULT 0,
  `format` varchar(100) NOT NULL,
  `error` text NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

UPDATE config SET conf_value = 151 WHERE conf_name = 'schema';
