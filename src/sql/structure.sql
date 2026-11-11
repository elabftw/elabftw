-- @author Nicolas CARPi <nico-git@deltablot.email>
-- @copyright 2012 Nicolas CARPi
-- @see https://www.elabftw.net Official website
-- @license AGPL-3.0
-- @package elabftw

--
-- MySQL structure for getting a working elabftw database.
-- This file must be executed upon fresh installation.
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `elabftw`
--

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `token_hash` binary(32) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `can_write` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `userid` int(10) UNSIGNED NOT NULL,
  `team` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_api_keys_token_hash` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `category` INT UNSIGNED NOT NULL,
  `requester_userid` INT UNSIGNED NOT NULL,
  `target_userid` INT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- Table structure for table `authfail`
--

CREATE TABLE `authfail` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `users_id` int(10) UNSIGNED NOT NULL,
  `attempt_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `device_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_authfail_users_attempt_time` (`users_id`, `attempt_time`),
  KEY `idx_authfail_device_attempt_time` (`device_token`, `attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `api_keys`:
--   `userid`
--       `users` -> `userid`
--   `team`
--       `teams` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE `config` (
  `conf_name` varchar(255) NOT NULL,
  `conf_value` text,
  PRIMARY KEY (`conf_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `config`:
--

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `migration` CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `batch` INT UNSIGNED NOT NULL,
  `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`migration`),
  KEY `idx_schema_migrations_batch` (`batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `experiments`
--

CREATE TABLE `experiments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `team` int UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `body` mediumtext,
  `category` INT UNSIGNED NULL DEFAULT NULL,
  `custom_id` INT UNSIGNED NULL DEFAULT NULL,
  `status` INT UNSIGNED NULL DEFAULT NULL,
  `rating` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `userid` int(10) UNSIGNED NOT NULL,
  `elabid` varchar(255) NOT NULL,
  `locked` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `lockedby` int(10) UNSIGNED DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `signature_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_signed_at` TIMESTAMP NULL DEFAULT NULL,
  `last_signed_by` INT UNSIGNED NULL DEFAULT NULL,
  `timestamped` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `timestampedby` int(11) NULL DEFAULT NULL,
  `timestamped_at` timestamp NULL DEFAULT NULL,
  `canread_base` TINYINT UNSIGNED NOT NULL DEFAULT 30,
  `canwrite_base` TINYINT UNSIGNED NOT NULL DEFAULT 20,
  `canread` JSON NOT NULL,
  `canwrite` JSON NOT NULL,
  `canread_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `canwrite_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `content_type` tinyint NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_from_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_from_type` TINYINT UNSIGNED NULL DEFAULT NULL,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lastchangeby` int(10) UNSIGNED NULL DEFAULT NULL,
  `metadata` json NULL DEFAULT NULL,
  `metadata_search` LONGTEXT GENERATED ALWAYS AS (COALESCE(JSON_UNQUOTE(CONCAT(CHAR(34),
      REPLACE(REPLACE(REPLACE(JSON_EXTRACT(`metadata`, '$.extra_fields.*.value'),
        CHAR(92, 92), ' '), CHAR(92, 34), ' '), CHAR(34), ' '),
      CHAR(34))), '')) STORED,
  `hide_main_text` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `state` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `access_key` varchar(36) NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `experiments`:
--   `userid`
--       `users` -> `userid`
--   `category`
--       `status` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `experiments_changelog`
--

CREATE TABLE `experiments_changelog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` int(10) unsigned NOT NULL,
  `users_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `target` varchar(255) NOT NULL,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `experiments_comments`
--

CREATE TABLE `experiments_comments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `item_id` int(10) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  `immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `experiments_comments`:
--   `item_id`
--       `experiments` -> `id`
--   `userid`
--       `users` -> `userid`
--

-- --------------------------------------------------------

--
-- Table structure for table `experiments2items`
--

CREATE TABLE `experiments2items` (
  `item_id` int(10) UNSIGNED NOT NULL,
  `link_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`item_id`, `link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `experiments2items`:
--   `item_id`
--       `experiments` -> `id`
--   `link_id`
--       `items` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `experiments_request_actions`
--
CREATE TABLE IF NOT EXISTS `experiments_request_actions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `requester_userid` INT UNSIGNED NOT NULL,
    `target_userid` INT UNSIGNED NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `action` INT UNSIGNED NOT NULL,
    `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `fk_experiments_request_actions_experiments_id` (`entity_id`),
    CONSTRAINT `fk_experiments_request_actions_experiments_id`
      FOREIGN KEY (`entity_id`) REFERENCES `experiments` (`id`)
      ON DELETE CASCADE ON UPDATE CASCADE,
    KEY `fk_experiments_request_actions_requester_users_userid` (`requester_userid`),
    KEY `fk_experiments_request_actions_target_users_userid` (`target_userid`));

--
-- RELATIONSHIPS FOR TABLE `experiments_request_actions`:
--   `entity_id`
--       `experiments` -> `id`
--   `requester_userid`
--       `users` -> `userid`
--   `target_userid`
--       `users` -> `userid`
--

-- --------------------------------------------------------

--
-- Table structure for table `experiments_step_groups`
--

CREATE TABLE `experiments_step_groups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `ordering` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_experiments_step_groups_entity_id` (`entity_id`),
  CONSTRAINT `fk_experiments_step_groups_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `experiments_steps`
--

CREATE TABLE `experiments_steps` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` int(10) UNSIGNED NOT NULL,
  `group_id` INT UNSIGNED NULL DEFAULT NULL,
  `body` text NOT NULL,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  `finished` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `finished_time` datetime DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `deadline_notif` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `is_immutable` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_experiments_steps_group_id` (`group_id`),
  CONSTRAINT `fk_experiments_steps_group_id` FOREIGN KEY (`group_id`) REFERENCES `experiments_step_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `experiments_steps`:
--   `item_id`
--       `experiments` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `experiments_edit_mode`
--

CREATE TABLE `experiments_edit_mode` (
  `entity_id` int UNSIGNED NOT NULL,
  `locked_by` int UNSIGNED NOT NULL,
  `locked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `experiments_edit_mode`:
--   `entity_id`
--       `experiments` -> `id`
--   `locked_by`
--       `users` -> `userid`
--

-- --------------------------------------------------------

--
-- Table structure for table `experiments_templates`
--

CREATE TABLE `experiments_templates` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `team` int(10) UNSIGNED NOT NULL,
  `body` mediumtext NULL DEFAULT NULL,
  `category` INT UNSIGNED NULL DEFAULT NULL,
  `custom_id` INT UNSIGNED NULL DEFAULT NULL,
  `date` date NULL DEFAULT NULL,
  `elabid` varchar(255) NULL DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `userid` int(10) UNSIGNED DEFAULT NULL,
  `locked` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `lockedby` int(10) UNSIGNED DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `canread_base` TINYINT UNSIGNED NOT NULL DEFAULT 30,
  `canwrite_base` TINYINT UNSIGNED NOT NULL DEFAULT 20,
  `canread_target_base` TINYINT UNSIGNED NOT NULL DEFAULT 30,
  `canwrite_target_base` TINYINT UNSIGNED NOT NULL DEFAULT 20,
  `canread` JSON NOT NULL,
  `canwrite` JSON NOT NULL,
  `canread_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `canwrite_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `canread_target` JSON NOT NULL,
  `canwrite_target` JSON NOT NULL,
  `content_type` tinyint NOT NULL DEFAULT 1,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_from_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_from_type` TINYINT UNSIGNED NULL DEFAULT NULL,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lastchangeby` int(10) UNSIGNED NULL DEFAULT NULL,
  `metadata` json NULL DEFAULT NULL,
  `metadata_search` LONGTEXT GENERATED ALWAYS AS (COALESCE(JSON_UNQUOTE(CONCAT(CHAR(34),
      REPLACE(REPLACE(REPLACE(JSON_EXTRACT(`metadata`, '$.extra_fields.*.value'),
        CHAR(92, 92), ' '), CHAR(92, 34), ' '), CHAR(34), ' '),
      CHAR(34))), '')) STORED,
  `hide_main_text` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `state` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `status` INT UNSIGNED NULL DEFAULT NULL,
  `signature_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_signed_at` TIMESTAMP NULL DEFAULT NULL,
  `last_signed_by` INT UNSIGNED NULL DEFAULT NULL,
  `timestamped` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `timestampedby` int(11) NULL DEFAULT NULL,
  `timestamped_at` timestamp NULL DEFAULT NULL,
  `access_key` varchar(36) NULL DEFAULT NULL,
  `rating` tinyint UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `experiments_templates`:
--   `team`
--       `teams` -> `id`
--   `userid`
--       `users` -> `userid`
--

-- --------------------------------------------------------
--
-- Table structure for table `experiments_templates_changelog`
--

CREATE TABLE `experiments_templates_changelog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` int(10) unsigned NOT NULL,
  `users_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `target` varchar(255) NOT NULL,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `experiments_templates_comments`
--

CREATE TABLE `experiments_templates_comments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `item_id` int(10) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  `immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `experiments_templates_comments`:
--   `item_id`
--       `experiments_templates` -> `id`
--   `userid`
--       `users` -> `userid`
--

-- --------------------------------------------------------
--
-- Table structure for table `exports`
--

CREATE TABLE `exports` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `requester_userid` int UNSIGNED NOT NULL,
  `state` tinyint UNSIGNED NOT NULL DEFAULT 4,
  `long_name` varchar(255) DEFAULT NULL,
  `filesize` bigint UNSIGNED NULL DEFAULT NULL,
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

--
-- Table structure for table `experiments_templates_edit_mode`
--

CREATE TABLE `experiments_templates_edit_mode` (
  `entity_id` int UNSIGNED NOT NULL,
  `locked_by` int UNSIGNED NOT NULL,
  `locked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `experiments_edit_mode`:
--   `entity_id`
--       `experiments_templates` -> `id`
--   `locked_by`
--       `users` -> `userid`
--

-- --------------------------------------------------------

--
-- Table structure for table `experiments_templates_request_actions`
--
CREATE TABLE IF NOT EXISTS `experiments_templates_request_actions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `requester_userid` INT UNSIGNED NOT NULL,
    `target_userid` INT UNSIGNED NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `action` INT UNSIGNED NOT NULL,
    `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `fk_experiments_templates_request_actions_experiments_id` (`entity_id`),
    CONSTRAINT `fk_experiments_templates_request_actions_experiments_id`
      FOREIGN KEY (`entity_id`) REFERENCES `experiments_templates` (`id`)
      ON DELETE CASCADE ON UPDATE CASCADE,
    KEY `fk_experiments_templates_request_actions_requester_users_userid` (`requester_userid`),
    KEY `fk_experiments_templates_request_actions_target_users_userid` (`target_userid`));

--
-- RELATIONSHIPS FOR TABLE `experiments_templates_request_actions`:
--   `entity_id`
--       `experiments_templates` -> `id`
--   `requester_userid`
--       `users` -> `userid`
--   `target_userid`
--       `users` -> `userid`
--

-- --------------------------------------------------------

--
-- Table structure for table `favtags2users`
--

CREATE TABLE `favtags2users` (
  `users_id` int UNSIGNED NOT NULL,
  `tags_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`users_id`, `tags_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `idps`
--

CREATE TABLE `idps` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `entityid` varchar(255) NOT NULL,
  `enabled` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `source` tinyint UNSIGNED NULL DEFAULT NULL,
  `email_attr` varchar(255) NOT NULL,
  `team_attr` varchar(255) NULL DEFAULT NULL,
  `fname_attr` varchar(255) NULL DEFAULT NULL,
  `lname_attr` varchar(255) NULL DEFAULT NULL,
  `orgid_attr` varchar(255) NULL DEFAULT NULL,
  `orcid_attr` varchar(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `idps_sources`
--

CREATE TABLE `idps_sources` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `url` TEXT NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auto_refresh` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `last_fetched_at` timestamp NULL DEFAULT NULL,
  `enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_url` (`url`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `idps_certs`
--

CREATE TABLE `idps_certs` (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idp                 INT UNSIGNED NOT NULL,
    purpose             TINYINT UNSIGNED NOT NULL,
    x509                TEXT NOT NULL,
    sha256              CHAR(64) NOT NULL,
    not_before          DATETIME NULL,
    not_after           DATETIME NULL,
    is_active           TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_idps_certs_idp_id
        FOREIGN KEY (idp)
        REFERENCES idps(id)
        ON DELETE CASCADE,
    UNIQUE KEY uniq_idp_purpose_fpr (idp, purpose, sha256),
    KEY idx_idp_purpose_active (idp, purpose, is_active, not_before, not_after)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `idps_endpoints`
--

CREATE TABLE `idps_endpoints` (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idp                 INT UNSIGNED NOT NULL,
    binding             TINYINT UNSIGNED NOT NULL,
    location            VARCHAR(255) NOT NULL,
    is_slo              TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_idps_endpoints_idp_id
        FOREIGN KEY (idp)
        REFERENCES idps(id)
        ON DELETE CASCADE,
    UNIQUE KEY uniq_idp_bdg_loc (idp, binding, location, is_slo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `team` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_from_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_from_type` TINYINT UNSIGNED NULL DEFAULT NULL,
  `date` date NOT NULL,
  `body` mediumtext,
  `elabid` varchar(255) NOT NULL,
  `rating` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `category` INT UNSIGNED NULL DEFAULT NULL,
  `custom_id` INT UNSIGNED NULL DEFAULT NULL,
  `locked` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `lockedby` int(10) UNSIGNED DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  `canread_base` TINYINT UNSIGNED NOT NULL DEFAULT 30,
  `canwrite_base` TINYINT UNSIGNED NOT NULL DEFAULT 20,
  `canread` JSON NOT NULL,
  `canwrite` JSON NOT NULL,
  `canread_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `canwrite_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `canbook_base` TINYINT UNSIGNED NOT NULL DEFAULT 30,
  `canbook` JSON NOT NULL,
  `content_type` tinyint NOT NULL DEFAULT 1,
  `available` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lastchangeby` int(10) UNSIGNED NULL DEFAULT NULL,
  `metadata` json NULL DEFAULT NULL,
  `metadata_search` LONGTEXT GENERATED ALWAYS AS (COALESCE(JSON_UNQUOTE(CONCAT(CHAR(34),
      REPLACE(REPLACE(REPLACE(JSON_EXTRACT(`metadata`, '$.extra_fields.*.value'),
        CHAR(92, 92), ' '), CHAR(92, 34), ' '), CHAR(34), ' '),
      CHAR(34))), '')) STORED,
  `hide_main_text` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `state` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `status` INT UNSIGNED NULL DEFAULT NULL,
  `signature_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_signed_at` TIMESTAMP NULL DEFAULT NULL,
  `last_signed_by` INT UNSIGNED NULL DEFAULT NULL,
  `timestamped` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `timestampedby` int NULL DEFAULT NULL,
  `timestamped_at` timestamp NULL DEFAULT NULL,
  `access_key` varchar(36) NULL DEFAULT NULL,
  `is_bookable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `requires_booking_approval` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `book_max_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
  `book_max_slots` INT UNSIGNED NOT NULL DEFAULT 0,
  `book_can_overlap` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `book_users_can_in_past` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `book_is_cancellable` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `book_cancel_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
  `booking_window_days` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `booking_hourly_rate_notax` DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00,
  `booking_hourly_rate_tax` DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00,
  `booking_hourly_rate_currency` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_procurable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `proc_pack_qty` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `proc_price_notax` DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00,
  `proc_price_tax` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `proc_currency` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `items`:
--   `team`
--       `teams` -> `id`
--   `category`
--       `items_types` -> `id`
--   `userid`
--       `users` -> `userid`
--

-- --------------------------------------------------------
CREATE TABLE `items_categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `team` int UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `color` varchar(6) NOT NULL,
  `color_fg` char(6) NOT NULL DEFAULT 'ffffff',
  `is_private` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `ordering` int UNSIGNED DEFAULT NULL,
  `state` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- Table structure for table `items_changelog`
--

CREATE TABLE `items_changelog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` int(10) unsigned NOT NULL,
  `users_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `target` varchar(255) NOT NULL,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items_comments`
--

CREATE TABLE `items_comments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `item_id` int(10) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  `immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `items_comments`:
--   `item_id`
--       `items` -> `id`
--   `userid`
--       `users` -> `userid`
--

-- --------------------------------------------------------

--
-- Table structure for table `items_request_actions`
--

CREATE TABLE IF NOT EXISTS `items_request_actions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `requester_userid` INT UNSIGNED NOT NULL,
    `target_userid` INT UNSIGNED NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `action` INT UNSIGNED NOT NULL,
    `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `fk_items_request_actions_items_id` (`entity_id`),
    CONSTRAINT `fk_items_request_actions_items_id`
      FOREIGN KEY (`entity_id`) REFERENCES `items` (`id`)
      ON DELETE CASCADE ON UPDATE CASCADE,
    KEY `fk_items_request_actions_requester_users_userid` (`requester_userid`),
    KEY `fk_items_request_actions_target_users_userid` (`target_userid`));

--
-- RELATIONSHIPS FOR TABLE `items_request_actions`:
--   `entity_id`
--       `items` -> `id`
--   `requester_userid`
--       `users` -> `userid`
--   `target_userid`
--       `users` -> `userid`
--

-- --------------------------------------------------------

--
-- Table structure for table `items_edit_mode`
--

CREATE TABLE `items_edit_mode` (
  `entity_id` int UNSIGNED NOT NULL,
  `locked_by` int UNSIGNED NOT NULL,
  `locked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `items_edit_mode`:
--   `entity_id`
--       `items` -> `id`
--   `locked_by`
--       `users` -> `userid`
--

-- --------------------------------------------------------

--
-- Table structure for table `items_types`
--

CREATE TABLE `items_types` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` INT UNSIGNED NOT NULL,
  `team` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` date NULL DEFAULT NULL,
  `elabid` varchar(255) NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_from_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_from_type` TINYINT UNSIGNED NULL DEFAULT NULL,
  `category` INT UNSIGNED NULL DEFAULT NULL,
  `color` varchar(6) DEFAULT '29aeb9',
  `custom_id` INT UNSIGNED NULL DEFAULT NULL,
  `body` mediumtext NULL DEFAULT NULL,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  `content_type` tinyint NOT NULL DEFAULT 1,
  `canread_base` TINYINT UNSIGNED NOT NULL DEFAULT 30,
  `canwrite_base` TINYINT UNSIGNED NOT NULL DEFAULT 20,
  `canread` JSON NOT NULL,
  `canwrite` JSON NOT NULL,
  `canread_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `canwrite_is_immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `canread_target_base` TINYINT UNSIGNED NOT NULL DEFAULT 30,
  `canwrite_target_base` TINYINT UNSIGNED NOT NULL DEFAULT 20,
  `canbook_base` TINYINT UNSIGNED NOT NULL DEFAULT 30,
  `canbook` JSON NOT NULL,
  `canread_target` JSON NOT NULL,
  `canwrite_target` JSON NOT NULL,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lastchangeby` int(10) UNSIGNED NULL DEFAULT NULL,
  `locked` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `lockedby` int UNSIGNED DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `metadata` json NULL DEFAULT NULL,
  `metadata_search` LONGTEXT GENERATED ALWAYS AS (COALESCE(JSON_UNQUOTE(CONCAT(CHAR(34),
      REPLACE(REPLACE(REPLACE(JSON_EXTRACT(`metadata`, '$.extra_fields.*.value'),
        CHAR(92, 92), ' '), CHAR(92, 34), ' '), CHAR(34), ' '),
      CHAR(34))), '')) STORED,
  `hide_main_text` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `state` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `status` INT UNSIGNED NULL DEFAULT NULL,
  `signature_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_signed_at` TIMESTAMP NULL DEFAULT NULL,
  `last_signed_by` INT UNSIGNED NULL DEFAULT NULL,
  `timestamped` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `timestampedby` int(11) NULL DEFAULT NULL,
  `timestamped_at` timestamp NULL DEFAULT NULL,
  `access_key` varchar(36) NULL DEFAULT NULL,
  `rating` tinyint UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `items_types`:
--   `team`
--       `teams` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `items_types_changelog`
--

CREATE TABLE `items_types_changelog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` int(10) unsigned NOT NULL,
  `users_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `target` varchar(255) NOT NULL,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `items_types_comments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `item_id` int(10) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  `immutable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
--
-- Table structure for table `items_types2items`
--

CREATE TABLE `items_types2items` (
  `item_id` int UNSIGNED NOT NULL,
  `link_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`item_id`, `link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items_types_step_groups`
--

CREATE TABLE `items_types_step_groups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `ordering` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_items_types_step_groups_entity_id` (`entity_id`),
  CONSTRAINT `fk_items_types_step_groups_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `items_types_steps`
--

CREATE TABLE `items_types_steps` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` int UNSIGNED NOT NULL,
  `group_id` INT UNSIGNED NULL DEFAULT NULL,
  `body` text NOT NULL,
  `ordering` int UNSIGNED DEFAULT NULL,
  `finished` tinyint NOT NULL DEFAULT 0,
  `finished_time` datetime DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `deadline_notif` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `is_immutable` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_items_types_steps_group_id` (`group_id`),
  CONSTRAINT `fk_items_types_steps_group_id` FOREIGN KEY (`group_id`) REFERENCES `items_types_step_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items_types_edit_mode`
--

CREATE TABLE `items_types_edit_mode` (
  `entity_id` int UNSIGNED NOT NULL,
  `locked_by` int UNSIGNED NOT NULL,
  `locked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `items_edit_mode`:
--   `entity_id`
--       `items_types` -> `id`
--   `locked_by`
--       `users` -> `userid`
--

--
-- Table structure for table `items_types_request_actions`
--

CREATE TABLE IF NOT EXISTS `items_types_request_actions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `requester_userid` INT UNSIGNED NOT NULL,
    `target_userid` INT UNSIGNED NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `action` INT UNSIGNED NOT NULL,
    `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `fk_items_types_request_actions_items_id` (`entity_id`),
    CONSTRAINT `fk_items_types_request_actions_items_id`
      FOREIGN KEY (`entity_id`) REFERENCES `items_types` (`id`)
      ON DELETE CASCADE ON UPDATE CASCADE,
    KEY `fk_items_types_request_actions_requester_users_userid` (`requester_userid`),
    KEY `fk_items_types_request_actions_target_users_userid` (`target_userid`));

--
-- RELATIONSHIPS FOR TABLE `items_types_request_actions`:
--   `entity_id`
--       `items_types` -> `id`
--   `requester_userid`
--       `users` -> `userid`
--   `target_userid`
--       `users` -> `userid`
--

-- --------------------------------------------------------

--
-- Table structure for table `lockout_devices`
--

CREATE TABLE `lockout_devices` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `locked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `device_token` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(10) UNSIGNED NOT NULL,
  `category` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `send_email` tinyint NOT NULL DEFAULT 0,
  `email_sent` tinyint NOT NULL DEFAULT 0,
  `email_sent_at` datetime DEFAULT NULL,
  `is_ack` tinyint NOT NULL DEFAULT 0,
  `body` json DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;


--
-- Table structure for table `pin_experiments2users`
--

CREATE TABLE `pin_experiments2users` (
  `users_id` int UNSIGNED NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`users_id`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `pin_experiments_templates2users`
--

CREATE TABLE `pin_experiments_templates2users` (
  `users_id` int UNSIGNED NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`users_id`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `pin_items2users`
--

CREATE TABLE `pin_items2users` (
  `users_id` int UNSIGNED NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`users_id`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `pin_items_types2users` (
  `users_id` int UNSIGNED NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`users_id`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `procurement_requests`
--

CREATE TABLE IF NOT EXISTS `procurement_requests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `team` INT UNSIGNED NOT NULL,
    `requester_userid` INT UNSIGNED NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `qty_ordered` INT UNSIGNED NOT NULL DEFAULT 1,
    `qty_received` INT UNSIGNED NOT NULL DEFAULT 0,
    `body` TEXT NULL DEFAULT NULL,
    `quote` INT UNSIGNED NULL DEFAULT NULL,
    `email_sent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `email_sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `state` TINYINT UNSIGNED NOT NULL DEFAULT 10,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `experiments_status`
--

CREATE TABLE `experiments_status` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `team` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `color` varchar(6) NOT NULL,
  `is_private` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  `state` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `status`:
--   `team`
--       `teams` -> `id`
--

CREATE TABLE `experiments_categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `team` int UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `title` varchar(255) NOT NULL,
  `color` varchar(6) NOT NULL,
  `color_fg` char(6) NOT NULL DEFAULT 'ffffff',
  `is_private` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `ordering` int UNSIGNED DEFAULT NULL,
  `state` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
--
-- Table structure for table `items_status`
--

CREATE TABLE `items_status` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `team` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `color` varchar(6) NOT NULL,
  `is_private` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  `state` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `items_status`:
--   `team`
--       `teams` -> `id`
--

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sig_keys` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `pubkey` TEXT NULL DEFAULT NULL,
  `privkey` TEXT NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `userid` int UNSIGNED NOT NULL,
  `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `type` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
);

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `team` int(10) UNSIGNED NOT NULL,
  `tag` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `tags`:
--   `team`
--       `teams` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `tags2entity`
--

CREATE TABLE `tags2entity` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL,
  `item_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tags2entity_tag_type_item` (`tag_id`, `item_type`, `item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `tags2entity`:
--   `tag_id`
--       `tags` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `user_create_tag` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `force_exp_tpl` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `force_res_tpl` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `users_canwrite_experiments` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `users_canwrite_experiments_categories` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `users_canwrite_experiments_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `users_canwrite_experiments_templates` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `users_canwrite_resources` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `users_canwrite_resources_categories` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `users_canwrite_resources_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `users_canwrite_resources_templates` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `orgid` varchar(255) NULL DEFAULT NULL,
  `visible` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `announcement` varchar(255) NULL DEFAULT NULL,
  `onboarding_email_active` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `onboarding_email_subject` VARCHAR(255) NULL,
  `onboarding_email_body` TEXT NULL,
  `newcomer_threshold` INT UNSIGNED NOT NULL DEFAULT 15,
  `newcomer_banner` TEXT NULL DEFAULT NULL,
  `newcomer_banner_active` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `capture_container_deletion_reason` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `teams`:
--

-- --------------------------------------------------------

--
-- Table structure for table `team_events`
--

CREATE TABLE `team_events` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `team` int(10) UNSIGNED NOT NULL,
  `item` int(10) UNSIGNED NOT NULL,
  `start` DATETIME NOT NULL,
  `end` DATETIME NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  `experiment` int(10) UNSIGNED DEFAULT NULL,
  `item_link` int(10) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- 0: no approval, 1: pending, 2: approved, 3: rejected (Overdue is UI-only)
  `approval_status` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `approved_by` INT UNSIGNED NULL DEFAULT NULL,
  `approved_at` DATETIME NULL DEFAULT NULL,
  `approval_comment` TEXT NULL DEFAULT NULL,
  `recurrence_id` CHAR(36) DEFAULT NULL,
  `recurrence_index` SMALLINT UNSIGNED DEFAULT NULL,
  `recurrence_frequency` VARCHAR(7) DEFAULT NULL,
  `recurrence_interval` SMALLINT UNSIGNED DEFAULT NULL,
  `recurrence_rule` JSON DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_end_after_start` CHECK (`end` >= `start`),
  CONSTRAINT `chk_team_events_recurrence_metadata`
    CHECK (
      (`recurrence_id` IS NULL AND `recurrence_index` IS NULL
        AND `recurrence_frequency` IS NULL AND `recurrence_interval` IS NULL AND `recurrence_rule` IS NULL)
      OR
      (`recurrence_id` IS NOT NULL AND `recurrence_index` IS NOT NULL
        AND `recurrence_frequency` IS NOT NULL AND `recurrence_interval` IS NOT NULL AND `recurrence_rule` IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- Indexes for table `team_events`
--
CREATE INDEX `idx_team_events_item_start_end` ON `team_events` (`item`, `start`, `end`);
CREATE INDEX `idx_team_events_team_start_end` ON `team_events` (`team`, `start`, `end`);
CREATE INDEX `idx_team_events_user_start_end` ON `team_events` (`userid`, `start`, `end`);
CREATE INDEX `idx_team_events_approval` ON `team_events` (`item`, `approval_status`, `start`);
CREATE UNIQUE INDEX `uniq_team_events_recurrence_index` ON `team_events` (`recurrence_id`, `recurrence_index`);

--
-- RELATIONSHIPS FOR TABLE `team_events`:
--   `team`
--       `teams` -> `id`
--   `userid`
--       `users` -> `userid`
--

-- --------------------------------------------------------

--
-- Table structure for table `team_groups`
--

CREATE TABLE `team_groups` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `team` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `team_groups`:
--   `team`
--       `teams` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `todolist`
--

CREATE TABLE `todolist` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `body` text NOT NULL,
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `todolist`:
--   `userid`
--       `users` -> `userid`
--

-- --------------------------------------------------------

--
-- Table structure for table `upload_groups`
--

CREATE TABLE `upload_groups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_id` INT UNSIGNED NOT NULL,
  `entity_type` TINYINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `ordering` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_upload_groups_entity` (`entity_id`, `entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `real_name` text NOT NULL,
  `long_name` text NOT NULL,
  `comment` text NULL DEFAULT NULL,
  `item_id` int(10) UNSIGNED DEFAULT NULL,
  `group_id` INT UNSIGNED NULL DEFAULT NULL,
  `ordering` INT UNSIGNED NULL DEFAULT NULL,
  `userid` int UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(128) DEFAULT NULL,
  `hash_algorithm` varchar(10) DEFAULT NULL,
  `storage` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `filesize` bigint UNSIGNED NULL DEFAULT NULL,
  `state` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `immutable` tinyint UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_uploads_group_id` (`group_id`),
  KEY `idx_uploads_long_name_state` (`long_name`(64), `state`),
  CONSTRAINT `fk_uploads_group_id` FOREIGN KEY (`group_id`) REFERENCES `upload_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `uploads`:
--   `userid`
--       `users` -> `userid`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `password_hash` varchar(255) NULL DEFAULT NULL,
  `password_modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mfa_secret` varchar(32) NULL DEFAULT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `is_sysadmin` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `orcid` varchar(19) NULL DEFAULT NULL,
  `orgid` varchar(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `token_hash` BINARY(32) NULL DEFAULT NULL,
  `token_created_at` TIMESTAMP NULL DEFAULT NULL,
  `limit_nb` tinyint UNSIGNED NOT NULL DEFAULT 15,
  `sc_create` varchar(1) NOT NULL DEFAULT 'c',
  `sc_edit` varchar(1) NOT NULL DEFAULT 'e',
  `sc_favorite` varchar(1) NOT NULL DEFAULT 'f',
  `sc_todo` varchar(1) NOT NULL DEFAULT 't',
  `sc_search` varchar(1) NOT NULL DEFAULT 's',
  `scope_experiments` tinyint UNSIGNED NOT NULL DEFAULT 2,
  `scope_events` tinyint UNSIGNED NOT NULL DEFAULT 3,
  `scope_items` tinyint UNSIGNED NOT NULL DEFAULT 2,
  `scope_items_types` tinyint UNSIGNED NOT NULL DEFAULT 2,
  `scope_experiments_templates` tinyint UNSIGNED NOT NULL DEFAULT 2,
  `use_isodate` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `uploads_layout` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `validated` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `lang` varchar(5) NOT NULL DEFAULT 'en_GB',
  `default_read_base` TINYINT UNSIGNED NOT NULL DEFAULT 30,
  `default_write_base` TINYINT UNSIGNED NOT NULL DEFAULT 20,
  `default_read` JSON NOT NULL,
  `default_write` JSON NOT NULL,
  `cjk_fonts` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `orderby` varchar(255) NOT NULL DEFAULT 'lastchange',
  `sort` varchar(255) NOT NULL DEFAULT 'desc',
  `use_markdown` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `pdf_sig` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `inc_files_pdf` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `append_pdfs` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `disable_shortcuts` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `pdf_format` varchar(255) NOT NULL DEFAULT 'A4',
  `display_mode` VARCHAR(2) NOT NULL DEFAULT 'it',
  `last_login` DATETIME NULL DEFAULT NULL,
  `last_seen_version` INT UNSIGNED NOT NULL DEFAULT 40900,
  `allow_untrusted` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `notif_comment_created` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `notif_comment_created_email` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `notif_user_created` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `notif_user_created_email` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `notif_user_need_validation` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `notif_user_need_validation_email` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `notif_step_deadline` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `notif_step_deadline_email` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `notif_booking_approval_requested` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `notif_booking_approval_requested_email` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `notif_booking_approval_updated` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `notif_booking_approval_updated_email` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `notif_event_deleted` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `notif_event_deleted_email` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `auth_lock_time` datetime DEFAULT NULL,
  `auth_service` tinyint UNSIGNED NULL DEFAULT NULL,
  `valid_until` date NULL DEFAULT NULL,
  `entrypoint` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `always_show_owned` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `show_weekends` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `scheduler_layout` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `can_manage_users2teams` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `can_manage_compounds` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `can_manage_inventory_locations` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `theme_variant` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `primary_bg` CHAR(6) DEFAULT NULL,
  `primary_fg` CHAR(6) DEFAULT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mfa_rate_limits`
--

CREATE TABLE `mfa_rate_limits` (
  `users_id` int(10) UNSIGNED NOT NULL,
  `failed_attempts` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `first_failed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `locked_until` datetime DEFAULT NULL,
  PRIMARY KEY (`users_id`),
  CONSTRAINT `fk_mfa_rate_limits_user`
    FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users2team_groups`
--

CREATE TABLE `users2team_groups` (
  `userid` int(10) UNSIGNED NOT NULL,
  `groupid` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`userid`, `groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
--
-- RELATIONSHIPS FOR TABLE `users2team_groups`:
--   `groupid`
--       `team_groups` -> `id`
--   `userid`
--       `users` -> `userid`
--

-- Many-to-many approvers: a user may approve bookings on several resources.
CREATE TABLE `items_booking_approvers` (
  `entity_id` INT UNSIGNED NOT NULL,
  `userid` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`entity_id`, `userid`),
  INDEX `idx_items_booking_approvers_userid` (`userid`),
  CONSTRAINT `fk_items_booking_approvers_item` FOREIGN KEY (`entity_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_booking_approvers_user` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users2teams`
--

CREATE TABLE `users2teams` (
  `users_id` int(10) UNSIGNED NOT NULL,
  `teams_id` int(10) UNSIGNED NOT NULL,
  `is_owner` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_admin` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_archived` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`users_id`, `teams_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
--
-- RELATIONSHIPS FOR TABLE `users2teams`:
--   `teams_id`
--       `teams` -> `id`
--   `users_id`
--       `users` -> `userid`
--

-- --------------------------------------------------------

--
-- Table structure for table `experiments2experiments`
--

CREATE TABLE `experiments2experiments` (
  `item_id` int UNSIGNED NOT NULL,
  `link_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`item_id`, `link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `experiments2experiments`:
--   `item_id`
--       `experiments` -> `id`
--   `link_id`
--       `experiments` -> `id`
--

--
-- Table structure for table `experiments_templates2experiments`
--

CREATE TABLE `experiments_templates2experiments` (
  `item_id` int UNSIGNED NOT NULL,
  `link_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`item_id`, `link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `experiments_templates2experiments`:
--   `item_id`
--       `experiments_templates` -> `id`
--   `link_id`
--       `experiments` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `items2experiments`
--
CREATE TABLE `items2experiments` (
  `item_id` int UNSIGNED NOT NULL,
  `link_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`item_id`, `link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `items2experiments`:
--   `item_id`
--       `items` -> `id`
--   `link_id`
--       `experiments` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `items_types2experiments`
--
CREATE TABLE `items_types2experiments` (
  `item_id` int UNSIGNED NOT NULL,
  `link_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`item_id`, `link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `items_types2experiments`:
--   `item_id`
--       `items_types` -> `id`
--   `link_id`
--       `experiments` -> `id`
--

-- --------------------------------------------------------

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD KEY `fk_api_keys_users_id` (`userid`);

--
-- Indexes for table `experiments`
--
ALTER TABLE `experiments`
  ADD KEY `fk_experiments_users_userid` (`userid`),
  ADD KEY `idx_experiments_state` (`state`),
  ADD KEY `fk_experiments_status_id` (`status`),
  ADD UNIQUE `unique_experiments_custom_id` (`category`, `custom_id`);

--
-- Indexes for table `experiments_comments`
--
ALTER TABLE `experiments_comments`
  ADD KEY `fk_experiments_comments_experiments_id` (`item_id`),
  ADD KEY `fk_experiments_comments_users_userid` (`userid`);

--
-- Indexes for table `experiments2items`
--
ALTER TABLE `experiments2items`
  ADD KEY `fk_experiments2items_experiments_id` (`item_id`),
  ADD KEY `fk_experiments2items_items_id` (`link_id`);

--
-- Indexes for table `experiments_steps`
--
ALTER TABLE `experiments_steps`
  ADD KEY `fk_experiments_steps_experiments_id` (`item_id`);

--
-- Indexes for table `experiments_templates`
--
ALTER TABLE `experiments_templates`
  ADD KEY `fk_experiments_templates_teams_id` (`team`),
  ADD KEY `idx_experiments_templates_state` (`state`),
  ADD KEY `fk_experiments_templates_users_userid` (`userid`),
  ADD UNIQUE `unique_experiments_templates_custom_id` (`category`, `custom_id`);

--
-- Indexes and Constraints for table `experiments_templates_comments`
--

ALTER TABLE `experiments_templates_comments`
  ADD KEY `fk_experiments_templates_comments_experiments_templates_id` (`item_id`),
  ADD KEY `fk_experiments_templates_comments_users_userid` (`userid`),
  ADD CONSTRAINT `fk_experiments_templates_comments_experiments_templates_id` FOREIGN KEY (`item_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_templates_comments_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Indexes and Constraints for table `experiments_templates_changelog`
--
ALTER TABLE `experiments_templates_changelog`
  ADD KEY `fk_experiments_templates_changelog2experiments_templates_id` (`entity_id`),
  ADD KEY `fk_experiments_templates_changelog2users_userid` (`users_id`),
  ADD CONSTRAINT `fk_experiments_templates_changelog2experiments_templates_id` FOREIGN KEY (`entity_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_templates_changelog2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Indexes for table `favtags2users`
--
ALTER TABLE `favtags2users`
  ADD KEY `fk_favtags2users_tags_id` (`tags_id`),
  ADD KEY `fk_favtags2users_users_id` (`users_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD KEY `fk_items_teams_id` (`team`),
  ADD KEY `idx_items_state` (`state`),
  ADD KEY `fk_items_users_userid` (`userid`),
  ADD KEY `fk_items_category_items_categories_id` (`category`),
  ADD UNIQUE `unique_items_custom_id` (`category`, `custom_id`);

ALTER TABLE `items_categories`
  ADD KEY `fk_items_categories_teams_id` (`team`);
--
-- Indexes and Constraints for table `items_changelog`
--
ALTER TABLE `items_changelog`
  ADD KEY `fk_items_changelog2items_id` (`entity_id`),
  ADD KEY `fk_items_changelog2users_userid` (`users_id`),
  ADD CONSTRAINT `fk_items_changelog2items_id` FOREIGN KEY (`entity_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_changelog2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Indexes for table `items_comments`
--
ALTER TABLE `items_comments`
  ADD KEY `fk_items_comments_items_id` (`item_id`),
  ADD KEY `fk_items_comments_users_userid` (`userid`);

--
-- Indexes for table `items_types`
--
ALTER TABLE `items_types`
  ADD KEY `fk_items_types_teams_id` (`team`),
  ADD KEY `idx_items_types_state` (`state`),
  ADD UNIQUE `unique_items_types_custom_id` (`id`, `custom_id`);

--
-- Indexes and Constraints for table `items_types_changelog`
--
ALTER TABLE `items_types_changelog`
  ADD KEY `fk_items_types_changelog2items_types_id` (`entity_id`),
  ADD KEY `fk_items_types_changelog2users_userid` (`users_id`),
  ADD CONSTRAINT `fk_items_types_changelog2items_types_id` FOREIGN KEY (`entity_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_types_changelog2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `items_types_comments`
  ADD CONSTRAINT FOREIGN KEY (`item_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Indexes for table `items_types2items`
--
ALTER TABLE `items_types2items`
  ADD KEY `fk_items_types2items_items_id` (`item_id`),
  ADD KEY `fk_items_types2items_items_types_id` (`link_id`);

--
-- Indexes for table `items_types_steps`
--
ALTER TABLE `items_types_steps`
  ADD KEY `fk_items_types_steps_items_id` (`item_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD KEY `fk_notifications_users_userid` (`userid`);

--
-- Indexes for table `experiments_status`
--
ALTER TABLE `experiments_status`
  ADD KEY `fk_experiments_status_teams_team_id` (`team`);

--
-- Indexes for table `items_status`
--
ALTER TABLE `items_status`
  ADD KEY `fk_items_status_teams_team_id` (`team`);

--
-- Indexes for table `experiments_categories`
--
ALTER TABLE `experiments_categories`
  ADD KEY `fk_experiments_categories_teams_team_id` (`team`);

ALTER TABLE `items_categories`
  ADD CONSTRAINT `fk_items_categories_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Indexes for table `sig_keys`
--
ALTER TABLE `sig_keys`
  ADD KEY `fk_sig_keys_users_userid` (`userid`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD KEY `fk_tags_teams_id` (`team`);

--
-- Indexes for table `team_events`
--
ALTER TABLE `team_events`
  ADD KEY `fk_team_events_teams_id` (`team`),
  ADD KEY `fk_team_events_users_userid` (`userid`);

--
-- Indexes for table `team_groups`
--
ALTER TABLE `team_groups`
  ADD KEY `fk_team_groups_teams_id` (`team`);

--
-- Indexes for table `todolist`
--
ALTER TABLE `todolist`
  ADD KEY `fk_todolist_users_userid` (`userid`);

--
-- Indexes for table `experiments2experiments`
--
ALTER TABLE `experiments2experiments`
  ADD KEY `fk_experiments2experiments_item_id` (`item_id`),
  ADD KEY `fk_experiments2experiments_link_id` (`link_id`);

--
-- Indexes for table `experiments_templates2experiments`
--
ALTER TABLE `experiments_templates2experiments`
  ADD KEY `fk_experiments_templates2experiments_item_id` (`item_id`),
  ADD KEY `fk_experiments_templates2experiments_link_id` (`link_id`);

--
-- Indexes for table `items2experiments`
--
ALTER TABLE `items2experiments`
  ADD KEY `fk_items2experiments_item_id` (`item_id`),
  ADD KEY `fk_items2experiments_link_id` (`link_id`);

--
-- Indexes for table `items_types2experiments`
--
ALTER TABLE `items_types2experiments`
  ADD KEY `fk_items_types2experiments_item_id` (`item_id`),
  ADD KEY `fk_items_types2experiments_link_id` (`link_id`);
--
-- Constraints for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD CONSTRAINT `fk_api_keys_users_id` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_api_keys_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_api_keys_user_team` FOREIGN KEY (`userid`, `team`) REFERENCES `users2teams` (`users_id`, `teams_id`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Constraints for table `experiments`
--
ALTER TABLE `experiments`
  ADD CONSTRAINT `fk_experiments_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_status_id` FOREIGN KEY (`status`) REFERENCES `experiments_status` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `experiments_changelog`
--

ALTER TABLE `experiments_changelog`
  ADD KEY `fk_experiments_changelog2experiments_id` (`entity_id`),
  ADD KEY `fk_experiments_changelog2users_userid` (`users_id`),
  ADD CONSTRAINT `fk_experiments_changelog2experiments_id` FOREIGN KEY (`entity_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_changelog2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments_comments`
--
ALTER TABLE `experiments_comments`
  ADD CONSTRAINT `fk_experiments_comments_experiments_id` FOREIGN KEY (`item_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_comments_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments_request_actions`
--
ALTER TABLE `experiments_request_actions`
  ADD CONSTRAINT `fk_experiments_request_actions_requester_users_userid`
    FOREIGN KEY (`requester_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_request_actions_target_users_userid`
    FOREIGN KEY (`target_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments2items`
--
ALTER TABLE `experiments2items`
  ADD CONSTRAINT `fk_experiments2items_experiments_id` FOREIGN KEY (`item_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments2items_items_id` FOREIGN KEY (`link_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments_steps`
--
ALTER TABLE `experiments_steps`
  ADD CONSTRAINT `fk_experiments_steps_experiments_id` FOREIGN KEY (`item_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments_edit_mode`
--
ALTER TABLE `experiments_edit_mode`
  ADD CONSTRAINT `fk_experiments_edit_mode_experiments_id`
    FOREIGN KEY (`entity_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_edit_mode_users_userid`
    FOREIGN KEY (`locked_by`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments_templates`
--
ALTER TABLE `experiments_templates`
  ADD CONSTRAINT `fk_experiments_templates_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_templates_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exports`
--
ALTER TABLE `exports`
  ADD CONSTRAINT `fk_exports_requester_userid_users_userid` FOREIGN KEY (`requester_userid`) REFERENCES `users`(`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments_templates_request_actions`
--
ALTER TABLE `experiments_templates_request_actions`
  ADD CONSTRAINT `fk_experiments_templates_request_actions_requester_users_userid`
    FOREIGN KEY (`requester_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_templates_request_actions_target_users_userid`
    FOREIGN KEY (`target_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `favtags2users`
--
ALTER TABLE `favtags2users`
  ADD CONSTRAINT `fk_favtags2users_tags_id` FOREIGN KEY (`tags_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_favtags2users_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `fk_items_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_category_items_categories_id` FOREIGN KEY (`category`) REFERENCES `items_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items_comments`
--
ALTER TABLE `items_comments`
  ADD CONSTRAINT `fk_items_comments_items_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_comments_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items_request_actions`
--
ALTER TABLE `items_request_actions`
  ADD CONSTRAINT `fk_items_request_actions_requester_users_userid`
    FOREIGN KEY (`requester_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_request_actions_target_users_userid`
    FOREIGN KEY (`target_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items_edit_mode`
--
ALTER TABLE `items_edit_mode`
  ADD CONSTRAINT `fk_items_edit_mode_items_id`
    FOREIGN KEY (`entity_id`) REFERENCES `items` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_edit_mode_users_userid`
    FOREIGN KEY (`locked_by`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items_types`
--
ALTER TABLE `items_types`
  ADD CONSTRAINT `fk_items_types_teams_id` FOREIGN KEY (`team`) REFERENCES `teams`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items_types2items`
--
ALTER TABLE `items_types2items`
  ADD CONSTRAINT `fk_items_types2items_items_id` FOREIGN KEY (`link_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_types2items_items_types_id` FOREIGN KEY (`item_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items_types_steps`
--
ALTER TABLE `items_types_steps`
  ADD CONSTRAINT `fk_items_types_steps_items_id` FOREIGN KEY (`item_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items_types_edit_mode`
--
ALTER TABLE `items_types_edit_mode`
  ADD CONSTRAINT `fk_items_types_edit_mode_items_types_id`
    FOREIGN KEY (`entity_id`) REFERENCES `items_types` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_types_edit_mode_users_userid`
    FOREIGN KEY (`locked_by`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items_request_actions`
--
ALTER TABLE `items_types_request_actions`
  ADD CONSTRAINT `fk_items_types_request_actions_requester_users_userid`
    FOREIGN KEY (`requester_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_types_request_actions_target_users_userid`
    FOREIGN KEY (`target_userid`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `status`
--
ALTER TABLE `experiments_status`
  ADD CONSTRAINT `fk_experiments_status_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `items_status`
  ADD CONSTRAINT `fk_items_status_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `experiments_categories`
  ADD CONSTRAINT `fk_experiments_categories_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sig_keys`
--
ALTER TABLE `sig_keys`
  ADD CONSTRAINT `fk_sig_keys_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tags`
--
ALTER TABLE `tags`
  ADD CONSTRAINT `fk_tags_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD UNIQUE `unique_tags_team_tag` (`team`, `tag`);

--
-- Constraints for table `team_events`
--
ALTER TABLE `team_events`
  ADD CONSTRAINT `fk_team_events_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_team_events_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `team_groups`
--
ALTER TABLE `team_groups`
  ADD CONSTRAINT `fk_team_groups_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `todolist`
--
ALTER TABLE `todolist`
  ADD CONSTRAINT `fk_todolist_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `uploads`
--
ALTER TABLE `uploads`
  ADD KEY `idx_uploads_item_id_type` (`item_id`, `type`),
  ADD KEY `fk_uploads_users_userid` (`userid`);
  -- ToDo: check if there is interference for cascading and if not add constraints
  -- ADD CONSTRAINT `fk_uploads_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

-- schema 49
CREATE TABLE `items_step_groups` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `ordering` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_items_step_groups_entity_id` (`entity_id`),
    CONSTRAINT `fk_items_step_groups_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `items_steps` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `item_id` int(10) unsigned NOT NULL,
    `group_id` INT UNSIGNED NULL DEFAULT NULL,
    `body` text NOT NULL,
    `ordering` int(10) unsigned DEFAULT NULL,
    `finished` tinyint UNSIGNED NOT NULL DEFAULT 0,
    `finished_time` datetime DEFAULT NULL,
    `deadline` datetime DEFAULT NULL,
    `deadline_notif` tinyint UNSIGNED NOT NULL DEFAULT 0,
    `is_immutable` tinyint UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_items_steps_items_id` (`item_id`),
    KEY `idx_items_steps_group_id` (`group_id`),
    CONSTRAINT `fk_items_steps_items_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_items_steps_group_id` FOREIGN KEY (`group_id`) REFERENCES `items_step_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE `experiments_templates_step_groups` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `ordering` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_experiments_templates_step_groups_entity_id` (`entity_id`),
    CONSTRAINT `fk_experiments_templates_step_groups_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `experiments_templates_steps` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `item_id` int(10) unsigned NOT NULL,
    `group_id` INT UNSIGNED NULL DEFAULT NULL,
    `body` text NOT NULL,
    `ordering` int(10) unsigned DEFAULT NULL,
    `finished` tinyint UNSIGNED NOT NULL DEFAULT 0,
    `finished_time` datetime DEFAULT NULL,
    `deadline` datetime DEFAULT NULL,
    `deadline_notif` tinyint UNSIGNED NOT NULL DEFAULT 0,
    `is_immutable` tinyint UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_experiments_templates_steps_items_id` (`item_id`),
    KEY `idx_experiments_templates_steps_group_id` (`group_id`),
    CONSTRAINT `fk_experiments_templates_steps_items_id` FOREIGN KEY (`item_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_experiments_templates_steps_group_id` FOREIGN KEY (`group_id`) REFERENCES `experiments_templates_step_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE `items2items` (
    `item_id` int(10) unsigned NOT NULL,
    `link_id` int(10) unsigned NOT NULL,
    PRIMARY KEY (`item_id`, `link_id`),
    KEY `fk_items2items_items_id` (`item_id`),
    KEY `fk_items2items_items_id2` (`link_id`),
    CONSTRAINT `fk_items2items_items_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_items2items_items_id2` FOREIGN KEY (`link_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE `experiments_templates2items` (
    `item_id` int(10) unsigned NOT NULL,
    `link_id` int(10) unsigned NOT NULL,
    PRIMARY KEY (`item_id`, `link_id`),
    KEY `fk_experiments_templates2items_items_id` (`item_id`),
    KEY `fk_experiments_templates2items_items_id2` (`link_id`),
    CONSTRAINT `fk_experiments_templates2items_experiments_templates_id` FOREIGN KEY (`item_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_experiments_templates2items_items_id` FOREIGN KEY (`link_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- schema 169
CREATE TABLE IF NOT EXISTS compounds (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL,
  `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modified_by` INT UNSIGNED NOT NULL,
  `userid` INT UNSIGNED NOT NULL,
  `team` INT UNSIGNED NOT NULL,
  `state` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `canread_base` TINYINT UNSIGNED NOT NULL DEFAULT 30,
  `canwrite_base` TINYINT UNSIGNED NOT NULL DEFAULT 20,
  `canread` JSON NOT NULL,
  `canwrite` JSON NOT NULL,
  `name` TEXT NULL DEFAULT NULL,
  `molecular_formula` VARCHAR(255) NULL DEFAULT NULL,
  `cas_number` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `ec_number` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `chebi_id` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `chembl_id` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `dea_number` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `drugbank_id` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `dsstox_id` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `hmdb_id` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `inchi` MEDIUMTEXT NULL DEFAULT NULL,
  `inchi_key` CHAR(27) NULL DEFAULT NULL UNIQUE,
  `iupac_name` TEXT NULL DEFAULT NULL,
  `kegg_id` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `metabolomics_wb_id` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `molecular_weight` DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00,
  `nci_code` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `nikkaji_number` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `pharmgkb_id` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `pharos_ligand_id` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `pubchem_cid` INT UNSIGNED NULL DEFAULT NULL UNIQUE,
  `rxcui` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `smiles` MEDIUMTEXT NULL DEFAULT NULL,
  `unii` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `wikidata` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `wikipedia` VARCHAR(20) NULL DEFAULT NULL UNIQUE,
  `is_corrosive` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_explosive` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_flammable` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_gas_under_pressure` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_hazardous2env` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_hazardous2health` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_serious_health_hazard` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_oxidising` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_toxic` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_radioactive` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_antibiotic` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_antibiotic_precursor` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_drug` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_drug_precursor` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_explosive_precursor` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_cmr` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_nano` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_controlled` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_ed2health` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_ed2env` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_pbt` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_pmt` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_vpvb` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_vpvm` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `ghs_signal_word` ENUM('Danger', 'Warning') NULL DEFAULT NULL,
  primary key(`id`)
);

CREATE TABLE `compounds2experiments` (
  `compound_id` int(10) UNSIGNED NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`compound_id`, `entity_id`),
  CONSTRAINT `fk_c2e_compound_id_compounds_id` FOREIGN KEY (`compound_id`) REFERENCES `compounds`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_c2e_entity_id_experiments_id` FOREIGN KEY (`entity_id`) REFERENCES `experiments`(`id`) ON DELETE CASCADE
);
CREATE TABLE `compounds2experiments_templates` (
  `compound_id` int(10) UNSIGNED NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`compound_id`, `entity_id`),
  CONSTRAINT `fk_c2et_templates_compound_id_compounds_id` FOREIGN KEY (`compound_id`) REFERENCES `compounds`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_c2et_templates_entity_id_experiments_templates_id` FOREIGN KEY (`entity_id`) REFERENCES `experiments_templates`(`id`) ON DELETE CASCADE
);
CREATE TABLE `compounds2items` (
  `compound_id` int(10) UNSIGNED NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`compound_id`, `entity_id`),
  CONSTRAINT `fk_c2i_compound_id_compounds_id` FOREIGN KEY (`compound_id`) REFERENCES `compounds`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_c2i_entity_id_items_id` FOREIGN KEY (`entity_id`) REFERENCES `items`(`id`) ON DELETE CASCADE
);
CREATE TABLE `compounds2items_types` (
  `compound_id` int(10) UNSIGNED NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`compound_id`, `entity_id`),
  CONSTRAINT `fk_c2it_compound_id_compounds_id` FOREIGN KEY (`compound_id`) REFERENCES `compounds`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_c2it_entity_id_items_types_id` FOREIGN KEY (`entity_id`) REFERENCES `items_types`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS compounds_fingerprints (
  id   INT UNSIGNED NOT NULL,
  fp0  INT UNSIGNED NOT NULL,
  fp1  INT UNSIGNED NOT NULL,
  fp2  INT UNSIGNED NOT NULL,
  fp3  INT UNSIGNED NOT NULL,
  fp4  INT UNSIGNED NOT NULL,
  fp5  INT UNSIGNED NOT NULL,
  fp6  INT UNSIGNED NOT NULL,
  fp7  INT UNSIGNED NOT NULL,
  fp8  INT UNSIGNED NOT NULL,
  fp9  INT UNSIGNED NOT NULL,
  fp10 INT UNSIGNED NOT NULL,
  fp11 INT UNSIGNED NOT NULL,
  fp12 INT UNSIGNED NOT NULL,
  fp13 INT UNSIGNED NOT NULL,
  fp14 INT UNSIGNED NOT NULL,
  fp15 INT UNSIGNED NOT NULL,
  fp16 INT UNSIGNED NOT NULL,
  fp17 INT UNSIGNED NOT NULL,
  fp18 INT UNSIGNED NOT NULL,
  fp19 INT UNSIGNED NOT NULL,
  fp20 INT UNSIGNED NOT NULL,
  fp21 INT UNSIGNED NOT NULL,
  fp22 INT UNSIGNED NOT NULL,
  fp23 INT UNSIGNED NOT NULL,
  fp24 INT UNSIGNED NOT NULL,
  fp25 INT UNSIGNED NOT NULL,
  fp26 INT UNSIGNED NOT NULL,
  fp27 INT UNSIGNED NOT NULL,
  fp28 INT UNSIGNED NOT NULL,
  fp29 INT UNSIGNED NOT NULL,
  fp30 INT UNSIGNED NOT NULL,
  fp31 INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  primary key(`id`),
  FOREIGN KEY (id) REFERENCES compounds(id) ON DELETE CASCADE
);

-- GHS HAZARD AND PRECAUTIONARY STATEMENTS
-- The code is case-sensitive: H360FD and H360Fd are different statements.
CREATE TABLE IF NOT EXISTS `ghs_statements` (
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `kind` ENUM('H', 'EUH', 'P') NOT NULL,
  `statement` TEXT NOT NULL,
  -- withdrawn codes are kept when imported and stay readable, but cannot be added by hand anymore
  `is_deprecated` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ghs_statements_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `compounds2ghs_statements` (
  `compound_id` INT UNSIGNED NOT NULL,
  `statement_id` SMALLINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`compound_id`, `statement_id`),
  KEY `idx_c2ghs_statement_id` (`statement_id`),
  CONSTRAINT `fk_c2ghs_compound_id_compounds_id` FOREIGN KEY (`compound_id`) REFERENCES `compounds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_c2ghs_statement_id_ghs_statements_id` FOREIGN KEY (`statement_id`) REFERENCES `ghs_statements` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `ghs_statements` (`code`, `kind`, `statement`, `is_deprecated`) VALUES
  ('H200', 'H', 'Unstable explosives.', 0),
  ('H201', 'H', 'Explosive; mass explosion hazard.', 0),
  ('H202', 'H', 'Explosive, severe projection hazard.', 0),
  ('H203', 'H', 'Explosive; fire, blast or projection hazard.', 0),
  ('H204', 'H', 'Fire or projection hazard.', 0),
  ('H205', 'H', 'May mass explode in fire.', 0),
  ('H206', 'H', 'Fire, blast or projection hazard; increased risk of explosion if desensitising agent is reduced.', 0),
  ('H207', 'H', 'Fire or projection hazard; increased risk of explosion if desensitising agent is reduced.', 0),
  ('H208', 'H', 'Fire hazard; increased risk of explosion if desensitising agent is reduced.', 0),
  ('H209', 'H', 'Explosive.', 0),
  ('H210', 'H', 'Very sensitive.', 0),
  ('H211', 'H', 'May be sensitive.', 0),
  ('H220', 'H', 'Extremely flammable gas.', 0),
  ('H221', 'H', 'Flammable gas.', 0),
  ('H222', 'H', 'Extremely flammable aerosol.', 0),
  ('H223', 'H', 'Flammable aerosol.', 0),
  ('H224', 'H', 'Extremely flammable liquid and vapour.', 0),
  ('H225', 'H', 'Highly flammable liquid and vapour.', 0),
  ('H226', 'H', 'Flammable liquid and vapour.', 0),
  ('H227', 'H', 'Combustible liquid.', 0),
  ('H228', 'H', 'Flammable solid.', 0),
  ('H229', 'H', 'Pressurised container: May burst if heated.', 0),
  ('H230', 'H', 'May react explosively even in the absence of air.', 0),
  ('H231', 'H', 'May react explosively even in the absence of air at elevated pressure and/or temperature.', 0),
  ('H232', 'H', 'May ignite spontaneously if exposed to air.', 0),
  ('H240', 'H', 'Heating may cause an explosion.', 0),
  ('H241', 'H', 'Heating may cause a fire or explosion.', 0),
  ('H242', 'H', 'Heating may cause a fire.', 0),
  ('H250', 'H', 'Catches fire spontaneously if exposed to air.', 0),
  ('H251', 'H', 'Self-heating: may catch fire.', 0),
  ('H252', 'H', 'Self-heating in large quantities; may catch fire.', 0),
  ('H260', 'H', 'In contact with water releases flammable gases which may ignite spontaneously.', 0),
  ('H261', 'H', 'In contact with water releases flammable gases.', 0),
  ('H270', 'H', 'May cause or intensify fire; oxidiser.', 0),
  ('H271', 'H', 'May cause fire or explosion; strong oxidiser.', 0),
  ('H272', 'H', 'May intensify fire; oxidiser.', 0),
  ('H280', 'H', 'Contains gas under pressure; may explode if heated.', 0),
  ('H281', 'H', 'Contains refrigerated gas; may cause cryogenic burns or injury.', 0),
  ('H282', 'H', 'Extremely flammable chemical under pressure: may explode if heated.', 0),
  ('H283', 'H', 'Flammable chemical under pressure: may explode if heated.', 0),
  ('H284', 'H', 'Chemical under pressure: may explode if heated.', 0),
  ('H290', 'H', 'May be corrosive to metals.', 0),
  ('H300', 'H', 'Fatal if swallowed.', 0),
  ('H300+H310', 'H', 'Fatal if swallowed or in contact with skin', 0),
  ('H300+H310+H330', 'H', 'Fatal if swallowed, in contact with skin or if inhaled', 0),
  ('H300+H330', 'H', 'Fatal if swallowed or if inhaled', 0),
  ('H301', 'H', 'Toxic if swallowed.', 0),
  ('H301+H311', 'H', 'Toxic if swallowed or in contact with skin', 0),
  ('H301+H311+H331', 'H', 'Toxic if swallowed, in contact with skin or if inhaled', 0),
  ('H301+H331', 'H', 'Toxic if swallowed or if inhaled', 0),
  ('H302', 'H', 'Harmful if swallowed.', 0),
  ('H302+H312', 'H', 'Harmful if swallowed or in contact with skin', 0),
  ('H302+H312+H332', 'H', 'Harmful if swallowed, in contact with skin or if inhaled', 0),
  ('H302+H332', 'H', 'Harmful if swallowed or if inhaled', 0),
  ('H303', 'H', 'May be harmful if swallowed.', 0),
  ('H303+H313', 'H', 'May be harmful if swallowed or in contact with skin.', 0),
  ('H303+H313+H333', 'H', 'May be harmful if swallowed, in contact with skin or if inhaled.', 0),
  ('H303+H333', 'H', 'May be harmful if swallowed or if inhaled.', 0),
  ('H304', 'H', 'May be fatal if swallowed and enters airways.', 0),
  ('H305', 'H', 'May be harmful if swallowed and enters airways.', 0),
  ('H310', 'H', 'Fatal in contact with skin.', 0),
  ('H310+H330', 'H', 'Fatal in contact with skin or if inhaled', 0),
  ('H311', 'H', 'Toxic in contact with skin.', 0),
  ('H311+H331', 'H', 'Toxic in contact with skin or if inhaled', 0),
  ('H312', 'H', 'Harmful in contact with skin.', 0),
  ('H312+H332', 'H', 'Harmful in contact with skin or if inhaled', 0),
  ('H313', 'H', 'May be harmful in contact with skin.', 0),
  ('H313+H333', 'H', 'May be harmful in contact with skin or if inhaled.', 0),
  ('H314', 'H', 'Causes severe skin burns and eye damage.', 0),
  ('H315', 'H', 'Causes skin irritation.', 0),
  ('H315+H320', 'H', 'Causes skin and eye irritation.', 0),
  ('H316', 'H', 'Causes mild skin irritation.', 0),
  ('H317', 'H', 'May cause an allergic skin reaction.', 0),
  ('H318', 'H', 'Causes serious eye damage.', 0),
  ('H319', 'H', 'Causes serious eye irritation.', 0),
  ('H320', 'H', 'Causes eye irritation.', 0),
  ('H330', 'H', 'Fatal if inhaled.', 0),
  ('H331', 'H', 'Toxic if inhaled.', 0),
  ('H332', 'H', 'Harmful if inhaled.', 0),
  ('H333', 'H', 'May be harmful if inhaled.', 0),
  ('H334', 'H', 'May cause allergy or asthma symptoms or breathing difficulties if inhaled.', 0),
  ('H335', 'H', 'May cause respiratory irritation.', 0),
  ('H336', 'H', 'May cause drowsiness or dizziness.', 0),
  ('H340', 'H', 'May cause genetic defects.', 0),
  ('H341', 'H', 'Suspected of causing genetic defects.', 0),
  ('H350', 'H', 'May cause cancer.', 0),
  ('H350i', 'H', 'May cause cancer by inhalation.', 0),
  ('H351', 'H', 'Suspected of causing cancer.', 0),
  ('H360', 'H', 'May damage fertility or the unborn child.', 0),
  ('H360D', 'H', 'May damage the unborn child.', 0),
  ('H360Df', 'H', 'May damage the unborn child. Suspected of damaging fertility.', 0),
  ('H360F', 'H', 'May damage fertility.', 0),
  ('H360FD', 'H', 'May damage fertility. May damage the unborn child.', 0),
  ('H360Fd', 'H', 'May damage fertility. Suspected of damaging the unborn child.', 0),
  ('H361', 'H', 'Suspected of damaging fertility or the unborn child.', 0),
  ('H361d', 'H', 'Suspected of damaging the unborn child.', 0),
  ('H361f', 'H', 'Suspected of damaging fertility.', 0),
  ('H361fd', 'H', 'Suspected of damaging fertility. Suspected of damaging the unborn child.', 0),
  ('H362', 'H', 'May cause harm to breast-fed children.', 0),
  ('H370', 'H', 'Causes damage to organs.', 0),
  ('H371', 'H', 'May cause damage to organs.', 0),
  ('H372', 'H', 'Causes damage to organs through prolonged or repeated exposure.', 0),
  ('H373', 'H', 'May cause damage to organs through prolonged or repeated exposure.', 0),
  ('H400', 'H', 'Very toxic to aquatic life.', 0),
  ('H401', 'H', 'Toxic to aquatic life.', 0),
  ('H402', 'H', 'Harmful to aquatic life.', 0),
  ('H410', 'H', 'Very toxic to aquatic life with long lasting effects.', 0),
  ('H411', 'H', 'Toxic to aquatic life with long lasting effects.', 0),
  ('H412', 'H', 'Harmful to aquatic life with long lasting effects.', 0),
  ('H413', 'H', 'May cause long lasting harmful effects to aquatic life.', 0),
  ('H420', 'H', 'Harms public health and the environment by destroying ozone in the upper atmosphere', 0),
  ('EUH001', 'EUH', 'Explosive when dry.', 1),
  ('EUH006', 'EUH', 'Explosive with or without contact with air.', 1),
  ('EUH014', 'EUH', 'Reacts violently with water.', 0),
  ('EUH018', 'EUH', 'In use may form flammable/explosive vapour-air mixture.', 0),
  ('EUH019', 'EUH', 'May form explosive peroxides.', 0),
  ('EUH029', 'EUH', 'Contact with water liberates toxic gas.', 0),
  ('EUH031', 'EUH', 'Contact with acids liberates toxic gas.', 0),
  ('EUH032', 'EUH', 'Contact with acids liberates very toxic gas.', 0),
  ('EUH044', 'EUH', 'Risk of explosion if heated under confinement.', 0),
  ('EUH059', 'EUH', 'Hazardous to the ozone layer.', 1),
  ('EUH066', 'EUH', 'Repeated exposure may cause skin dryness or cracking.', 0),
  ('EUH070', 'EUH', 'Toxic by eye contact.', 0),
  ('EUH071', 'EUH', 'Corrosive to the respiratory tract.', 0),
  ('EUH201', 'EUH', 'Contains lead. Should not be used on surfaces liable to be chewed or sucked by children.', 0),
  ('EUH201A', 'EUH', 'Warning! Contains lead.', 0),
  ('EUH202', 'EUH', 'Cyanoacrylate. Danger. Bonds skin and eyes in seconds. Keep out of the reach of children.', 0),
  ('EUH203', 'EUH', 'Contains chromium (VI). May produce an allergic reaction.', 0),
  ('EUH204', 'EUH', 'Contains isocyanates. May produce an allergic reaction.', 0),
  ('EUH205', 'EUH', 'Contains epoxy constituents. May produce an allergic reaction.', 0),
  ('EUH206', 'EUH', 'Warning! Do not use together with other products. May release dangerous gases (chlorine).', 0),
  ('EUH207', 'EUH', 'Warning! Contains cadmium. Dangerous fumes are formed during use. See information supplied by the manufacturer. Comply with the safety instructions.', 0),
  ('EUH208', 'EUH', 'Contains …. May produce an allergic reaction.', 0),
  ('EUH209', 'EUH', 'Can become highly flammable in use.', 0),
  ('EUH209A', 'EUH', 'Can become flammable in use.', 0),
  ('EUH210', 'EUH', 'Safety data sheet available on request.', 0),
  ('EUH211', 'EUH', 'Warning! Hazardous respirable droplets may be formed when sprayed. Do not breathe spray or mist.', 0),
  ('EUH212', 'EUH', 'Warning! Hazardous respirable dust may be formed when used. Do not breathe dust.', 0),
  ('EUH380', 'EUH', 'May cause endocrine disruption in humans', 0),
  ('EUH381', 'EUH', 'Suspected of causing endocrine disruption in humans', 0),
  ('EUH401', 'EUH', 'To avoid risks to human health and the environment, comply with the instructions for use.', 0),
  ('EUH430', 'EUH', 'May cause endocrine disruption in the environment', 0),
  ('EUH431', 'EUH', 'Suspected of causing endocrine disruption in the environment', 0),
  ('EUH440', 'EUH', 'Accumulates in the environment and living organisms including in humans', 0),
  ('EUH441', 'EUH', 'Strongly accumulates in the environment and living organisms including in humans', 0),
  ('EUH450', 'EUH', 'Can cause long-lasting and diffuse contamination of water resources', 0),
  ('EUH451', 'EUH', 'Can cause very long-lasting and diffuse contamination of water resources', 0),
  ('P101', 'P', 'If medical advice is needed, have product container or label at hand.', 0),
  ('P102', 'P', 'Keep out of reach of children.', 0),
  ('P103', 'P', 'Read label before use.', 0),
  ('P201', 'P', 'Obtain special instructions before use.', 0),
  ('P202', 'P', 'Do not handle until all safety precautions have been read and understood.', 0),
  ('P203', 'P', 'Obtain, read and follow all safety instructions before use.', 0),
  ('P210', 'P', 'Keep away from heat, hot surfaces, sparks, open flames and other ignition sources. No smoking.', 0),
  ('P211', 'P', 'Do not spray on an open flame or other ignition source.', 0),
  ('P212', 'P', 'Avoid heating under confinement or reduction of the desensitising agent.', 0),
  ('P220', 'P', 'Keep away from clothing and other combustible materials.', 0),
  ('P221', 'P', 'Take any precaution to avoid mixing with combustibles/…', 1),
  ('P222', 'P', 'Do not allow contact with air.', 0),
  ('P223', 'P', 'Do not allow contact with water.', 0),
  ('P230', 'P', 'Keep wetted with…', 0),
  ('P231', 'P', 'Handle and store contents under inert gas/…', 0),
  ('P231+P232', 'P', 'Handle and store contents under inert gas/…. Protect from moisture.', 0),
  ('P232', 'P', 'Protect from moisture.', 0),
  ('P233', 'P', 'Keep container tightly closed.', 0),
  ('P234', 'P', 'Keep only in original packaging.', 0),
  ('P235', 'P', 'Keep cool.', 0),
  ('P235+P410', 'P', 'Keep cool. Protect from sunlight.', 1),
  ('P236', 'P', 'Keep only in original packaging; Division … in the transport configuration.', 0),
  ('P240', 'P', 'Ground and bond container and receiving equipment.', 0),
  ('P241', 'P', 'Use explosion-proof [electrical/ventilating/lighting/…] equipment.', 0),
  ('P242', 'P', 'Use non-sparking tools.', 0),
  ('P243', 'P', 'Take action to prevent static discharges.', 0),
  ('P244', 'P', 'Keep valves and fittings free from oil and grease.', 0),
  ('P250', 'P', 'Do not subject to grinding/shock/friction/….', 0),
  ('P251', 'P', 'Do not pierce or burn, even after use.', 0),
  ('P260', 'P', 'Do not breathe dust/fume/gas/mist/vapours/spray.', 0),
  ('P261', 'P', 'Avoid breathing dust/fume/gas/mist/vapours/spray.', 0),
  ('P262', 'P', 'Do not get in eyes, on skin, or on clothing.', 0),
  ('P263', 'P', 'Avoid contact during pregnancy and while nursing.', 0),
  ('P264', 'P', 'Wash … thoroughly after handling.', 0),
  ('P264+P265', 'P', 'Wash hands [and …] thoroughly after handling. Do not touch eyes.', 0),
  ('P265', 'P', 'Do not touch eyes.', 0),
  ('P270', 'P', 'Do not eat, drink or smoke when using this product.', 0),
  ('P271', 'P', 'Use only outdoors or in a well-ventilated area.', 0),
  ('P272', 'P', 'Contaminated work clothing should not be allowed out of the workplace.', 0),
  ('P273', 'P', 'Avoid release to the environment.', 0),
  ('P280', 'P', 'Wear protective gloves/protective clothing/eye protection/face protection.', 0),
  ('P281', 'P', 'Use personal protective equipment as required.', 1),
  ('P282', 'P', 'Wear cold insulating gloves and either face shield or eye protection.', 0),
  ('P283', 'P', 'Wear fire resistant or flame retardant clothing.', 0),
  ('P284', 'P', '[In case of inadequate ventilation] wear respiratory protection.', 0),
  ('P285', 'P', 'In case of inadequate ventilation wear respiratory protection.', 1),
  ('P301', 'P', 'IF SWALLOWED:', 0),
  ('P301+P310', 'P', 'IF SWALLOWED: Immediately call a POISON CENTER/doctor/…', 0),
  ('P301+P312', 'P', 'IF SWALLOWED: Call a POISON CENTRE/doctor/… if you feel unwell.', 0),
  ('P301+P316', 'P', 'IF SWALLOWED: Get emergency medical help immediately.', 0),
  ('P301+P317', 'P', 'IF SWALLOWED: Get medical help.', 0),
  ('P301+P330+P331', 'P', 'IF SWALLOWED: Rinse mouth. Do NOT induce vomiting.', 0),
  ('P302', 'P', 'IF ON SKIN:', 0),
  ('P302+P317', 'P', 'IF ON SKIN: Get medical help.', 0),
  ('P302+P334', 'P', 'IF ON SKIN: Immerse in cool water or wrap in wet bandages.', 0),
  ('P302+P335+P334', 'P', 'IF ON SKIN: Brush off loose particles from skin. Immerse in cool water [or wrap in wet bandages].', 0),
  ('P302+P350', 'P', 'IF ON SKIN: Gently wash with plenty of soap and water.', 1),
  ('P302+P352', 'P', 'IF ON SKIN: Wash with plenty of water/…', 0),
  ('P302+P361+P354', 'P', 'IF ON SKIN: Take off immediately all contaminated clothing. Immediately rinse with water for several minutes.', 0),
  ('P303', 'P', 'IF ON SKIN (or hair):', 0),
  ('P303+P361+P353', 'P', 'IF ON SKIN (or hair): Take off immediately all contaminated clothing. Rinse skin with water [or shower].', 0),
  ('P304', 'P', 'IF INHALED:', 0),
  ('P304+P312', 'P', 'IF INHALED: Call a POISON CENTER/doctor/… if you feel unwell.', 1),
  ('P304+P317', 'P', 'IF INHALED: Get medical help.', 0),
  ('P304+P340', 'P', 'IF INHALED: Remove person to fresh air and keep comfortable for breathing.', 0),
  ('P304+P341', 'P', 'IF INHALED: If breathing is difficult, remove victim to fresh air and keep at rest in a position comfortable for breathing.', 1),
  ('P305', 'P', 'IF IN EYES:', 0),
  ('P305+P351+P338', 'P', 'IF IN EYES: Rinse cautiously with water for several minutes. Remove contact lenses, if present and easy to do. Continue rinsing.', 0),
  ('P305+P354+P338', 'P', 'IF IN EYES: Immediately rinse with water for several minutes. Remove contact lenses if present and easy to do. Continue rinsing.', 0),
  ('P306', 'P', 'IF ON CLOTHING:', 0),
  ('P306+P360', 'P', 'IF ON CLOTHING: rinse immediately contaminated clothing and skin with plenty of water before removing clothes.', 0),
  ('P307', 'P', 'IF exposed:', 1),
  ('P307+P311', 'P', 'IF exposed: call a POISON CENTER or doctor/physician.', 1),
  ('P308', 'P', 'IF exposed or concerned:', 0),
  ('P308+P311', 'P', 'IF exposed or concerned: Call a POISON CENTER/doctor/…', 0),
  ('P308+P313', 'P', 'IF exposed or concerned: Get medical advice/attention.', 0),
  ('P308+P316', 'P', 'IF exposed or concerned: Get emergency medical help immediately.', 0),
  ('P309', 'P', 'IF exposed or if you feel unwell:', 1),
  ('P309+P311', 'P', 'IF exposed or if you feel unwell: call a POISON CENTER or doctor/physician.', 1),
  ('P310', 'P', 'Immediately call a POISON CENTER/doctor/…', 0),
  ('P311', 'P', 'Call a POISON CENTER/doctor/…', 0),
  ('P312', 'P', 'Call a POISON CENTRE/doctor/… if you feel unwell.', 0),
  ('P313', 'P', 'Get medical advice/attention.', 0),
  ('P314', 'P', 'Get medical advice/attention if you feel unwell.', 0),
  ('P315', 'P', 'Get immediate medical advice/attention.', 0),
  ('P316', 'P', 'Get emergency medical help immediately.', 0),
  ('P317', 'P', 'Get medical help.', 0),
  ('P318', 'P', 'IF exposed or concerned, get medical advice.', 0),
  ('P319', 'P', 'Get medical help if you feel unwell.', 0),
  ('P320', 'P', 'Specific treatment is urgent (see … on this label).', 0),
  ('P321', 'P', 'Specific treatment (see … on this label).', 0),
  ('P322', 'P', 'Specific measures (see …on this label).', 1),
  ('P330', 'P', 'Rinse mouth.', 0),
  ('P331', 'P', 'Do NOT induce vomiting.', 0),
  ('P332', 'P', 'If skin irritation occurs:', 0),
  ('P332+P313', 'P', 'If skin irritation occurs: Get medical advice/attention.', 0),
  ('P332+P317', 'P', 'If skin irritation occurs: Get medical help.', 0),
  ('P333', 'P', 'If skin irritation or rash occurs:', 0),
  ('P333+P313', 'P', 'If skin irritation or rash occurs: Get medical advice/attention.', 0),
  ('P333+P317', 'P', 'If skin irritation or rash occurs: Get medical help.', 0),
  ('P334', 'P', 'Immerse in cool water [or wrap in wet bandages].', 0),
  ('P335', 'P', 'Brush off loose particles from skin.', 0),
  ('P335+P334', 'P', 'Brush off loose particles from skin. Immerse in cool water/wrap in wet bandages.', 1),
  ('P336', 'P', 'Thaw frosted parts with lukewarm water. Do no rub affected area.', 0),
  ('P336+P315', 'P', 'Thaw frosted parts with lukewarm water. Do not rub affected area. Get immediate medical advice/attention.', 0),
  ('P336+P317', 'P', 'Immediately thaw frosted parts with lukewarm water. Do not rub affected area. Get medical help.', 0),
  ('P337', 'P', 'If eye irritation persists:', 0),
  ('P337+P313', 'P', 'If eye irritation persists: Get medical advice/attention.', 0),
  ('P337+P317', 'P', 'If eye irritation persists: Get medical help.', 0),
  ('P338', 'P', 'Remove contact lenses, if present and easy to do. Continue rinsing.', 0),
  ('P340', 'P', 'Remove person to fresh air and keep comfortable for breathing.', 0),
  ('P341', 'P', 'If breathing is difficult, remove victim to fresh air and keep at rest in a position comfortable for breathing.', 1),
  ('P342', 'P', 'If experiencing respiratory symptoms:', 0),
  ('P342+P311', 'P', 'If experiencing respiratory symptoms: Call a POISON CENTER/doctor/…', 0),
  ('P342+P316', 'P', 'If experiencing respiratory symptoms: Get emergency medical help immediately.', 0),
  ('P350', 'P', 'Gently wash with plenty of soap and water.', 1),
  ('P351', 'P', 'Rinse cautiously with water for several minutes.', 0),
  ('P352', 'P', 'Wash with plenty of water/…', 0),
  ('P353', 'P', 'Rinse skin with water [or shower].', 0),
  ('P354', 'P', 'Immediately rinse with water for several minutes.', 0),
  ('P360', 'P', 'Rinse immediately contaminated clothing and skin with plenty of water before removing clothes.', 0),
  ('P361', 'P', 'Take off immediately all contaminated clothing.', 0),
  ('P361+P364', 'P', 'Take off immediately all contaminated clothing and wash it before reuse.', 0),
  ('P362', 'P', 'Take off contaminated clothing.', 0),
  ('P362+P364', 'P', 'Take off contaminated clothing and wash it before reuse.', 0),
  ('P363', 'P', 'Wash contaminated clothing before reuse.', 0),
  ('P364', 'P', 'And wash it before reuse.', 0),
  ('P370', 'P', 'In case of fire:', 0),
  ('P370+P372+P380+P373', 'P', 'In case of fire: Explosion risk. Evacuate area. DO NOT fight fire when fire reaches explosives.', 0),
  ('P370+P376', 'P', 'In case of fire: Stop leak if safe to do so.', 0),
  ('P370+P378', 'P', 'In case of fire: Use… to extinguish.', 0),
  ('P370+P380', 'P', 'In case of fire: Evacuate area.', 1),
  ('P370+P380+P375', 'P', 'In case of fire: Evacuate area. Fight fire remotely due to the risk of explosion.', 0),
  ('P370+P380+P375[+P378]', 'P', 'In case of fire: Evacuate area. Fight fire remotely due to the risk of explosion. [Use … to extinguish].', 0),
  ('P371', 'P', 'In case of major fire and large quantities:', 0),
  ('P371+P380+P375', 'P', 'In case of major fire and large quantities: Evacuate area. Fight fire remotely due to the risk of explosion.', 0),
  ('P372', 'P', 'Explosion risk.', 0),
  ('P373', 'P', 'DO NOT fight fire when fire reaches explosives.', 0),
  ('P374', 'P', 'Fight fire with normal precautions from a reasonable distance.', 1),
  ('P375', 'P', 'Fight fire remotely due to the risk of explosion.', 0),
  ('P376', 'P', 'Stop leak if safe to do so.', 0),
  ('P377', 'P', 'Leaking gas fire: Do not extinguish, unless leak can be stopped safely.', 0),
  ('P378', 'P', 'Use… to extinguish.', 0),
  ('P380', 'P', 'Evacuate area.', 0),
  ('P381', 'P', 'In case of leakage, eliminate all ignition sources.', 0),
  ('P390', 'P', 'Absorb spillage to prevent material damage.', 0),
  ('P391', 'P', 'Collect spillage.', 0),
  ('P401', 'P', 'Store in accordance with….', 0),
  ('P402', 'P', 'Store in a dry place.', 0),
  ('P402+P404', 'P', 'Store in a dry place. Store in a closed container.', 0),
  ('P403', 'P', 'Store in a well-ventilated place.', 0),
  ('P403+P233', 'P', 'Store in a well-ventilated place. Keep container tightly closed.', 0),
  ('P403+P235', 'P', 'Store in a well-ventilated place. Keep cool.', 0),
  ('P404', 'P', 'Store in a closed container.', 0),
  ('P405', 'P', 'Store locked up.', 0),
  ('P406', 'P', 'Store in a corrosion-resistant/… container with a resistant inner liner.', 0),
  ('P407', 'P', 'Maintain air gap between stacks or pallets.', 0),
  ('P410', 'P', 'Protect from sunlight.', 0),
  ('P410+P403', 'P', 'Protect from sunlight. Store in a well-ventilated place.', 0),
  ('P410+P412', 'P', 'Protect from sunlight. Do no expose to temperatures exceeding 50 °C/122°F.', 0),
  ('P411', 'P', 'Store at temperatures not exceeding … °C/…°F.', 0),
  ('P411+P235', 'P', 'Store at temperatures not exceeding … °C/…°F. Keep cool.', 1),
  ('P412', 'P', 'Do not expose to temperatures exceeding 50 °C/122°F.', 0),
  ('P413', 'P', 'Store bulk masses greater than … kg/… lbs at temperatures not exceeding … °C/…°F.', 0),
  ('P420', 'P', 'Store separately.', 0),
  ('P422', 'P', 'Store contents under …', 1),
  ('P501', 'P', 'Dispose of contents/container to …', 0),
  ('P502', 'P', 'Refer to manufacturer or supplier for information on recovery or recycling.', 0),
  ('P503', 'P', 'Refer to manufacturer/supplier/… for information on disposal/recovery/recycling.', 0)
  ON DUPLICATE KEY UPDATE `kind` = VALUES(`kind`), `statement` = VALUES(`statement`), `is_deprecated` = VALUES(`is_deprecated`);

-- STORAGE UNITS
CREATE TABLE IF NOT EXISTS storage_units (
    id INT unsigned NOT NULL AUTO_INCREMENT,
    name VARCHAR(255),
    parent_id INT unsigned,
    -- NULL means unlimited
    capacity INT unsigned DEFAULT NULL,
    FOREIGN KEY (parent_id) REFERENCES storage_units(id) ON DELETE CASCADE,
    PRIMARY KEY(`id`)
);

-- STORAGE UNITS HISTORY
CREATE TABLE IF NOT EXISTS storage_units_history (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    storage_unit_id INT UNSIGNED NOT NULL,
    old_parent_id INT UNSIGNED NULL,
    new_parent_id INT UNSIGNED NULL,
    users_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY (storage_unit_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- CONTAINERS TO EXPERIMENTS
CREATE TABLE containers2experiments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    storage_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    qty_stored DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00,
    qty_unit VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (storage_id) REFERENCES storage_units(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES experiments(id) ON DELETE CASCADE,
    PRIMARY KEY(`id`)
);
-- CONTAINERS TO experiments_templates
CREATE TABLE containers2experiments_templates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    storage_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    qty_stored DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00,
    qty_unit VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (storage_id) REFERENCES storage_units(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES experiments_templates(id) ON DELETE CASCADE,
    PRIMARY KEY(`id`)
);
-- CONTAINERS TO items
CREATE TABLE containers2items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    storage_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    qty_stored DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00,
    qty_unit VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (storage_id) REFERENCES storage_units(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    PRIMARY KEY(`id`)
);
-- CONTAINERS TO ITEMS_TYPES
CREATE TABLE containers2items_types (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    storage_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    qty_stored DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00,
    qty_unit VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (storage_id) REFERENCES storage_units(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items_types(id) ON DELETE CASCADE,
    PRIMARY KEY(`id`)
);
-- end schema 167

-- schema 212
CREATE TABLE `instance2rors` (
  `ror` char(9) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`ror`),

  CONSTRAINT `chk_instance2rors_ror`
    CHECK (`ror` REGEXP '^0[a-hj-km-np-tv-z0-9]{6}[0-9]{2}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

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

CREATE TABLE `users2rors` (
  `users_id` int(10) UNSIGNED NOT NULL,
  `ror` char(9) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`users_id`, `ror`),
  KEY `idx_users2rors_ror` (`ror`),

  CONSTRAINT `fk_users2rors_user`
    FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT `chk_users2rors_ror`
    CHECK (`ror` REGEXP '^0[a-hj-km-np-tv-z0-9]{6}[0-9]{2}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
-- end schema 212

-- schema 214
CREATE TABLE branding (
    id TINYINT UNSIGNED NOT NULL,
    content_type VARCHAR(127) NOT NULL,
    data MEDIUMBLOB NOT NULL,
    filesize INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- schema 225
-- The pairing of scope with teams_id/users_id is not expressed as a CHECK constraint:
-- MySQL refuses a CHECK on a column that takes part in a foreign key referential action,
-- and the cascading deletes matter more. Without them a deleted team or user would leave
-- webhook rows behind that keep receiving events. The invariant is guaranteed in the model
-- layer instead, where each scope is its own class and hardcodes both values.
CREATE TABLE `webhooks` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope` int(10) UNSIGNED NOT NULL,
  `teams_id` int(10) UNSIGNED DEFAULT NULL,
  `users_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(512) NOT NULL,
  `secret` text NOT NULL,
  `events` json NOT NULL,
  `enabled` tinyint NOT NULL DEFAULT 1,
  `consecutive_failures` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `disabled_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_webhooks_scope` (`scope`, `enabled`),
  KEY `idx_webhooks_teams_id` (`teams_id`),
  KEY `idx_webhooks_users_id` (`users_id`),
  CONSTRAINT `fk_webhooks_teams_id` FOREIGN KEY (`teams_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_webhooks_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE `webhooks_queue` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `webhooks_id` int(10) UNSIGNED NOT NULL,
  `event_id` char(32) NOT NULL,
  `event` varchar(64) NOT NULL,
  `body` json NOT NULL,
  `state` tinyint NOT NULL DEFAULT 0,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `claim_token` char(32) DEFAULT NULL,
  `next_attempt_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `delivered_at` datetime DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_webhooks_queue_drain` (`state`, `next_attempt_at`),
  KEY `idx_webhooks_queue_claim_token` (`claim_token`),
  KEY `idx_webhooks_queue_webhooks_id` (`webhooks_id`),
  CONSTRAINT `fk_webhooks_queue_webhooks_id` FOREIGN KEY (`webhooks_id`) REFERENCES `webhooks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- Indexes and Constraints for table `experiments_templates_edit_mode`
--
ALTER TABLE `experiments_templates_edit_mode`
  ADD CONSTRAINT `fk_experiments_templates_edit_mode_experiments_templates_id`
    FOREIGN KEY (`entity_id`) REFERENCES `experiments_templates` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_templates_edit_mode_users_userid`
    FOREIGN KEY (`locked_by`) REFERENCES `users` (`userid`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Indexes and Constraints for table `users`
--
ALTER TABLE `users` ADD INDEX `idx_users_email_userid` (email, userid);
ALTER TABLE `users` ADD INDEX `idx_users_orgid_userid` (orgid, userid);
ALTER TABLE `users` ADD UNIQUE INDEX `idx_users_token_hash` (`token_hash`);

--
-- Indexes and Constraints for table `users2teams`
--
ALTER TABLE `users2teams`
  ADD KEY `fk_users2teams_teams_id` (`teams_id`),
  ADD KEY `idx_users_id_is_archived` (`users_id`, `is_archived`),
  ADD KEY `fk_users2teams_users_id` (`users_id`);
ALTER TABLE `users2teams`
  ADD CONSTRAINT `fk_users2teams_teams_id` FOREIGN KEY (`teams_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users2teams_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Indexes and Constraints for table `users2team_groups`
--
ALTER TABLE `users2team_groups`
  ADD KEY `fk_users2team_groups_groupid` (`groupid`),
  ADD KEY `fk_users2team_groups_userid` (`userid`);
ALTER TABLE `users2team_groups`
  ADD CONSTRAINT `fk_users2team_groups_groupid` FOREIGN KEY (`groupid`) REFERENCES `team_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users2team_groups_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments2experiments`
--
ALTER TABLE `experiments2experiments`
  ADD CONSTRAINT `fk_experiments2experiments_item_id`
    FOREIGN KEY (`item_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments2experiments_link_id`
    FOREIGN KEY (`link_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments_templates2experiments`
--
ALTER TABLE `experiments_templates2experiments`
  ADD CONSTRAINT `fk_experiments_templates2experiments_item_id`
    FOREIGN KEY (`item_id`) REFERENCES `experiments_templates` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_templates2experiments_link_id`
    FOREIGN KEY (`link_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items2experiments`
--
ALTER TABLE `items2experiments`
  ADD CONSTRAINT `fk_items2experiments_item_id`
    FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items2experiments_link_id`
    FOREIGN KEY (`link_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items_types2experiments`
--
ALTER TABLE `items_types2experiments`
  ADD CONSTRAINT `fk_items_types2experiments_item_id`
    FOREIGN KEY (`item_id`) REFERENCES `items_types` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_types2experiments_link_id`
    FOREIGN KEY (`link_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;


--
-- Constraints for table `pin_experiments_templates2users`
--
ALTER TABLE `pin_experiments_templates2users`
  ADD CONSTRAINT `fk_pin_experiments_templates2experiments_templates_id` FOREIGN KEY (`entity_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pin_experiments_templates2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Indexes for table `pin_experiments2users`
--
ALTER TABLE `pin_experiments2users`
  ADD KEY `fk_pin_experiments2users_userid` (`users_id`),
  ADD KEY `fk_pin_experiments2experiments_id` (`entity_id`);

--
-- Constraints for table `pin_experiments2users`
--
ALTER TABLE `pin_experiments2users`
  ADD CONSTRAINT `fk_pin_experiments2experiments_id` FOREIGN KEY (`entity_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pin_experiments2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Indexes for table `pin_experiments_templates2users`
--
ALTER TABLE `pin_experiments_templates2users`
  ADD KEY `fk_pin_experiments_templates2users_userid` (`users_id`),
  ADD KEY `fk_pin_experiments_templates2experiments_templates_id` (`entity_id`);

--
-- Indexes for table `pin_items2users`
--
ALTER TABLE `pin_items2users`
  ADD KEY `fk_pin_items2users_userid` (`users_id`),
  ADD KEY `fk_pin_items2items_id` (`entity_id`);

--
-- Constraints for table `pin_items2users`
--
ALTER TABLE `pin_items2users`
  ADD CONSTRAINT `fk_pin_items2items_id` FOREIGN KEY (`entity_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pin_items2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `pin_items_types2users`
  ADD CONSTRAINT FOREIGN KEY (`entity_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Indexes for table `procurement_requests`
--
ALTER TABLE `procurement_requests`
  ADD KEY `fk_teams_id_proc_team` (`team`),
  ADD KEY `fk_items_id_entity_id` (`entity_id`);

--
-- Constraints for table `procurement_requests`
--
ALTER TABLE `procurement_requests`
  ADD CONSTRAINT `fk_teams_id_proc_team` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_id_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- schema 210
CREATE UNIQUE INDEX uniq_tags2entity_type_item_tag
    ON tags2entity (item_type, item_id, tag_id);
-- end schema 210

-- schema 216
-- Add fulltext indexes used by the plain q search fast path.
ALTER TABLE `experiments`
  ADD FULLTEXT INDEX `idx_experiments_q` (`title`, `body`, `elabid`, `metadata_search`);

ALTER TABLE `items`
  ADD FULLTEXT INDEX `idx_items_q` (`title`, `body`, `elabid`, `metadata_search`);

ALTER TABLE `experiments_templates`
  ADD FULLTEXT INDEX `idx_experiments_templates_q` (`title`, `body`, `elabid`, `metadata_search`);

ALTER TABLE `items_types`
  ADD FULLTEXT INDEX `idx_items_types_q` (`title`, `body`, `elabid`, `metadata_search`);

ALTER TABLE `compounds`
  ADD FULLTEXT INDEX `idx_compounds_q` (
    `cas_number`,
    `ec_number`,
    `name`,
    `iupac_name`,
    `inchi_key`,
    `molecular_formula`
  );

ALTER TABLE `experiments`
  ADD INDEX `idx_experiments_state_date_id` (`state`, `date`, `id`),
  ADD INDEX `idx_experiments_state_modified_id` (`state`, `modified_at`, `id`),
  ADD INDEX idx_experiments_user_state_modified_id
  (userid, state, modified_at, id);

ALTER TABLE `items`
  ADD INDEX `idx_items_state_date_id` (`state`, `date`, `id`),
  ADD INDEX `idx_items_state_modified_id` (`state`, `modified_at`, `id`),
  ADD INDEX idx_items_user_state_modified_id
  (userid, state, modified_at, id);

ALTER TABLE `experiments_templates`
  ADD INDEX `idx_experiments_templates_state_created_id` (`state`, `created_at`, `id`),
  ADD INDEX `idx_experiments_templates_state_modified_id` (`state`, `modified_at`, `id`),
  ADD INDEX idx_experiments_templates_user_state_modified_id
  (userid, state, modified_at, id);

ALTER TABLE `items_types`
  ADD INDEX `idx_items_types_state_created_id` (`state`, `created_at`, `id`),
  ADD INDEX `idx_items_types_state_modified_id` (`state`, `modified_at`, `id`),
  ADD INDEX idx_items_types_user_state_modified_id
  (userid, state, modified_at, id);

ALTER TABLE `experiments_steps`
  ADD INDEX `idx_experiments_steps_next` (`item_id`, `finished`, `ordering`, `id`);

ALTER TABLE `items_steps`
  ADD INDEX `idx_items_steps_next` (`item_id`, `finished`, `ordering`, `id`);

ALTER TABLE `experiments_templates_steps`
  ADD INDEX `idx_experiments_templates_steps_next` (`item_id`, `finished`, `ordering`, `id`);

ALTER TABLE `items_types_steps`
  ADD INDEX `idx_items_types_steps_next` (`item_id`, `finished`, `ordering`, `id`);

ALTER TABLE `experiments_comments`
  ADD INDEX `idx_experiments_comments_item_created` (`item_id`, `created_at`);

ALTER TABLE `items_comments`
  ADD INDEX `idx_items_comments_item_created` (`item_id`, `created_at`);

ALTER TABLE `experiments_templates_comments`
  ADD INDEX `idx_experiments_templates_comments_item_created` (`item_id`, `created_at`);

ALTER TABLE `items_types_comments`
  ADD INDEX `idx_items_types_comments_item_created` (`item_id`, `created_at`);
-- end schema 216

-- First-class reaction records, owned by an entry through typed links.
CREATE TABLE reactions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  scheme JSON NULL,
  rxn MEDIUMTEXT NOT NULL,
  -- derived from the components on save: reactants>agents>products
  reaction_smiles MEDIUMTEXT NOT NULL,
  -- derived on save: SHA-256 of the sorted reactant and product InChIKeys, to find the same transformation
  reaction_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  preview_png MEDIUMTEXT NOT NULL,
  temperature_c DECIMAL(24,12) NULL,
  duration_s DECIMAL(24,12) NULL,
  pressure_pa DECIMAL(24,12) NULL,
  atmosphere VARCHAR(255) NOT NULL DEFAULT '',
  conditions_notes TEXT NOT NULL,
  reference_component_key CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
  yield_basis_component_key CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
  version INT UNSIGNED NOT NULL DEFAULT 1,
  state TINYINT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  modified_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by INT UNSIGNED NOT NULL,
  modified_by INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_reactions_reaction_key (reaction_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE reaction_components (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  reaction_id INT UNSIGNED NOT NULL,
  component_key CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  ordering SMALLINT UNSIGNED NOT NULL,
  role VARCHAR(20) NOT NULL,
  name TEXT NOT NULL,
  molfile MEDIUMTEXT NOT NULL,
  smiles MEDIUMTEXT NOT NULL,
  inchi_key VARCHAR(27) NOT NULL DEFAULT '',
  molecular_formula VARCHAR(255) NOT NULL DEFAULT '',
  molecular_weight DECIMAL(24,12) NULL,
  compound_id INT UNSIGNED NULL,
  item_id INT UNSIGNED NULL,
  material_form VARCHAR(10) NOT NULL DEFAULT 'neat',
  purity_percent DECIMAL(24,12) NOT NULL DEFAULT 100,
  density_g_ml DECIMAL(24,12) NULL,
  concentration_mol_l DECIMAL(24,12) NULL,
  coefficient DECIMAL(24,12) NOT NULL DEFAULT 1,
  planned_basis VARCHAR(16) NOT NULL DEFAULT 'mass',
  planned_unit VARCHAR(8) NOT NULL DEFAULT 'g',
  planned_value DECIMAL(24,12) NULL,
  actual_basis VARCHAR(16) NOT NULL DEFAULT 'mass',
  actual_unit VARCHAR(8) NOT NULL DEFAULT 'g',
  actual_value DECIMAL(24,12) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_reaction_component_key (reaction_id, component_key),
  KEY idx_reaction_components_inchi_key (inchi_key),
  CONSTRAINT fk_reaction_components_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_reaction_components_compound FOREIGN KEY (compound_id) REFERENCES compounds(id) ON DELETE SET NULL,
  CONSTRAINT fk_reaction_components_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- Open Babel FP2 fingerprints of the components, same layout as compounds_fingerprints, for substructure search
CREATE TABLE reaction_component_fingerprints (
  component_id INT UNSIGNED NOT NULL,
  fp0 INT UNSIGNED NOT NULL,
  fp1 INT UNSIGNED NOT NULL,
  fp2 INT UNSIGNED NOT NULL,
  fp3 INT UNSIGNED NOT NULL,
  fp4 INT UNSIGNED NOT NULL,
  fp5 INT UNSIGNED NOT NULL,
  fp6 INT UNSIGNED NOT NULL,
  fp7 INT UNSIGNED NOT NULL,
  fp8 INT UNSIGNED NOT NULL,
  fp9 INT UNSIGNED NOT NULL,
  fp10 INT UNSIGNED NOT NULL,
  fp11 INT UNSIGNED NOT NULL,
  fp12 INT UNSIGNED NOT NULL,
  fp13 INT UNSIGNED NOT NULL,
  fp14 INT UNSIGNED NOT NULL,
  fp15 INT UNSIGNED NOT NULL,
  fp16 INT UNSIGNED NOT NULL,
  fp17 INT UNSIGNED NOT NULL,
  fp18 INT UNSIGNED NOT NULL,
  fp19 INT UNSIGNED NOT NULL,
  fp20 INT UNSIGNED NOT NULL,
  fp21 INT UNSIGNED NOT NULL,
  fp22 INT UNSIGNED NOT NULL,
  fp23 INT UNSIGNED NOT NULL,
  fp24 INT UNSIGNED NOT NULL,
  fp25 INT UNSIGNED NOT NULL,
  fp26 INT UNSIGNED NOT NULL,
  fp27 INT UNSIGNED NOT NULL,
  fp28 INT UNSIGNED NOT NULL,
  fp29 INT UNSIGNED NOT NULL,
  fp30 INT UNSIGNED NOT NULL,
  fp31 INT UNSIGNED NOT NULL,
  PRIMARY KEY (component_id),
  CONSTRAINT fk_reaction_component_fingerprints_component FOREIGN KEY (component_id) REFERENCES reaction_components(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE reaction_revisions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  reaction_id INT UNSIGNED NOT NULL,
  version INT UNSIGNED NOT NULL,
  snapshot JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_reaction_revision (reaction_id, version),
  CONSTRAINT fk_reaction_revisions_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE reactions2experiments (
  reaction_id INT UNSIGNED NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  ordering INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (reaction_id),
  KEY idx_r2experiments_entity (entity_id, ordering),
  CONSTRAINT fk_r2experiments_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_r2experiments_entity FOREIGN KEY (entity_id) REFERENCES experiments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE reactions2items (
  reaction_id INT UNSIGNED NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  ordering INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (reaction_id),
  KEY idx_r2items_entity (entity_id, ordering),
  CONSTRAINT fk_r2items_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_r2items_entity FOREIGN KEY (entity_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE reactions2experiments_templates (
  reaction_id INT UNSIGNED NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  ordering INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (reaction_id),
  KEY idx_r2experiments_templates_entity (entity_id, ordering),
  CONSTRAINT fk_r2experiments_templates_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_r2experiments_templates_entity FOREIGN KEY (entity_id) REFERENCES experiments_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE reactions2items_types (
  reaction_id INT UNSIGNED NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  ordering INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (reaction_id),
  KEY idx_r2items_types_entity (entity_id, ordering),
  CONSTRAINT fk_r2items_types_reaction FOREIGN KEY (reaction_id) REFERENCES reactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_r2items_types_entity FOREIGN KEY (entity_id) REFERENCES items_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `experiments_body_revisions`
--

CREATE TABLE `experiments_body_revisions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL,
  `depth` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `body_zstd` LONGBLOB NOT NULL,
  `body_sha256` CHAR(64) NOT NULL,
  `content_type` TINYINT NOT NULL DEFAULT 1,
  `users_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_experiments_body_revisions_entity_id` (`entity_id`, `id`),
  KEY `idx_experiments_body_revisions_entity_id_created_at` (`entity_id`, `created_at`, `id`),
  CONSTRAINT `fk_experiments_body_revisions_experiments_id`
    FOREIGN KEY (`entity_id`) REFERENCES `experiments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_experiments_body_revisions_users_userid`
    FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- Table structure for table `items_body_revisions`
--

CREATE TABLE `items_body_revisions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL,
  `depth` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `body_zstd` LONGBLOB NOT NULL,
  `body_sha256` CHAR(64) NOT NULL,
  `content_type` TINYINT NOT NULL DEFAULT 1,
  `users_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_items_body_revisions_entity_id` (`entity_id`, `id`),
  KEY `idx_items_body_revisions_entity_id_created_at` (`entity_id`, `created_at`, `id`),
  CONSTRAINT `fk_items_body_revisions_items_id`
    FOREIGN KEY (`entity_id`) REFERENCES `items` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_body_revisions_users_userid`
    FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- Table structure for table `experiments_templates_body_revisions`
--

CREATE TABLE `experiments_templates_body_revisions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL,
  `depth` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `body_zstd` LONGBLOB NOT NULL,
  `body_sha256` CHAR(64) NOT NULL,
  `content_type` TINYINT NOT NULL DEFAULT 1,
  `users_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_experiments_templates_body_revisions_entity_id` (`entity_id`, `id`),
  KEY `idx_experiments_templates_body_revisions_entity_id_created_at` (`entity_id`, `created_at`, `id`),
  CONSTRAINT `fk_experiments_templates_body_revisions_experiments_templates_id`
    FOREIGN KEY (`entity_id`) REFERENCES `experiments_templates` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_experiments_templates_body_revisions_users_userid`
    FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- Table structure for table `items_types_body_revisions`
--

CREATE TABLE `items_types_body_revisions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL,
  `depth` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `body_zstd` LONGBLOB NOT NULL,
  `body_sha256` CHAR(64) NOT NULL,
  `content_type` TINYINT NOT NULL DEFAULT 1,
  `users_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_items_types_body_revisions_entity_id` (`entity_id`, `id`),
  KEY `idx_items_types_body_revisions_entity_id_created_at` (`entity_id`, `created_at`, `id`),
  CONSTRAINT `fk_items_types_body_revisions_items_types_id`
    FOREIGN KEY (`entity_id`) REFERENCES `items_types` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_types_body_revisions_users_userid`
    FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
