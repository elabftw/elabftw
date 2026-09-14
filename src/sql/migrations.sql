-- Migration history for changes after the fixed legacy schema baseline.
CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `batch` int unsigned NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `schema_migrations_migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
