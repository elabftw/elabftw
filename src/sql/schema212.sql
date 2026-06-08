-- schema 212
CREATE TABLE `teams2rors` (
  `teams_id` int(10) UNSIGNED NOT NULL,
  `ror` char(9) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`teams_id`, `ror`),
  KEY `idx_teams2rors_ror` (`ror`),

  CONSTRAINT `fk_teams2rors_team`
    FOREIGN KEY (`teams_id`) REFERENCES `teams` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT `chk_teams2rors_ror`
    CHECK (`ror` REGEXP '^0[a-hj-km-np-tv-z0-9]{6}[0-9]{2}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
