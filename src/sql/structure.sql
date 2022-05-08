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
  `hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `can_write` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `userid` int(10) UNSIGNED NOT NULL,
  `team` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- Table structure for table `authfail`
--

CREATE TABLE `authfail` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `users_id` int(10) UNSIGNED NOT NULL,
  `attempt_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `device_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
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
-- Table structure for table `experiments`
-- Here the datetime column cannot have current_timestamp on update because
-- of the way the code is in MySQL. It is fixed in 5.6 but we still target 5.5
--

CREATE TABLE `experiments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `body` mediumtext,
  `category` int(255) UNSIGNED NOT NULL,
  `rating` tinyint(10) UNSIGNED NOT NULL DEFAULT '0',
  `userid` int(10) UNSIGNED NOT NULL,
  `elabid` varchar(255) NOT NULL,
  `locked` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `lockedby` int(10) UNSIGNED DEFAULT NULL,
  `lockedwhen` timestamp NULL DEFAULT NULL,
  `timestamped` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `timestampedby` int(11) NULL DEFAULT NULL,
  `timestamptoken` text,
  `timestampedwhen` timestamp NULL DEFAULT NULL,
  `canread` varchar(255) NOT NULL DEFAULT 'team',
  `canwrite` varchar(255) NOT NULL DEFAULT 'user',
  `datetime` timestamp NULL DEFAULT NULL,
  `lastchange` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lastchangeby` int(10) UNSIGNED NULL DEFAULT NULL,
  `metadata` json NULL DEFAULT NULL,
  `state` int(10) UNSIGNED NOT NULL DEFAULT 1,
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
-- Table structure for table `experiments_comments`
--

CREATE TABLE `experiments_comments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
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
-- Table structure for table `experiments_links`
--

CREATE TABLE `experiments_links` (
  `item_id` int(10) UNSIGNED NOT NULL,
  `link_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`item_id`, `link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `experiments_links`:
--   `item_id`
--       `experiments` -> `id`
--   `link_id`
--       `items` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `experiments_revisions`
--

CREATE TABLE `experiments_revisions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` int(10) UNSIGNED NOT NULL,
  `body` mediumtext NOT NULL,
  `savedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` int(10) UNSIGNED NOT NULL,
  `metadata` json NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `experiments_revisions`:
--   `item_id`
--       `experiments` -> `id`
--   `userid`
--       `users` -> `userid`
--

-- --------------------------------------------------------

--
-- Table structure for table `experiments_steps`
--

CREATE TABLE `experiments_steps` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` int(10) UNSIGNED NOT NULL,
  `body` text NOT NULL,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  `finished` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `finished_time` datetime DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `deadline_notif` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `experiments_steps`:
--   `item_id`
--       `experiments` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `experiments_templates`
--

CREATE TABLE `experiments_templates` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `team` int(10) UNSIGNED DEFAULT NULL,
  `body` text,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` int(10) UNSIGNED DEFAULT NULL,
  `locked` tinyint(3) UNSIGNED DEFAULT NULL,
  `lockedby` int(10) UNSIGNED DEFAULT NULL,
  `lockedwhen` timestamp NULL DEFAULT NULL,
  `canread` varchar(255) NOT NULL,
  `canwrite` varchar(255) NOT NULL,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  `lastchange` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lastchangeby` int(10) UNSIGNED NULL DEFAULT NULL,
  `metadata` json NULL DEFAULT NULL,
  `state` int(10) UNSIGNED NOT NULL DEFAULT 1,
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
-- Table structure for table `experiments_templates_revisions`
--

CREATE TABLE `experiments_templates_revisions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` int(10) UNSIGNED NOT NULL,
  `body` mediumtext NOT NULL,
  `savedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `experiments_templates_revisions`:
--   `item_id`
--       `experiments_templates` -> `id`
--   `userid`
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

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_sysadmin` tinyint(1) UNSIGNED NOT NULL,
  `is_admin` tinyint(1) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `groups`:
--

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `name`, `is_sysadmin`, `is_admin`) VALUES
(1, 'Sysadmins', 1, '1'),
(2, 'Admins', 0, '1'),
(4, 'Users', 0, '0');

-- --------------------------------------------------------

--
-- Table structure for table `idps`
--

CREATE TABLE `idps` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `entityid` varchar(255) NOT NULL,
  `sso_url` varchar(255) NOT NULL,
  `sso_binding` varchar(255) NOT NULL,
  `slo_url` varchar(255) NOT NULL,
  `slo_binding` varchar(255) NOT NULL,
  `x509` text NOT NULL,
  `x509_new` text NOT NULL,
  `active` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `email_attr` varchar(255) NOT NULL,
  `team_attr` varchar(255) NULL DEFAULT NULL,
  `fname_attr` varchar(255) NULL DEFAULT NULL,
  `lname_attr` varchar(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `idps`:
--

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `team` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `body` mediumtext,
  `elabid` varchar(255) NOT NULL,
  `rating` tinyint(10) UNSIGNED NOT NULL DEFAULT '0',
  `category` int(255) UNSIGNED NOT NULL,
  `locked` tinyint(3) UNSIGNED DEFAULT NULL,
  `lockedby` int(10) UNSIGNED DEFAULT NULL,
  `lockedwhen` timestamp NULL DEFAULT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  `canread` varchar(255) NOT NULL DEFAULT 'team',
  `canwrite` varchar(255) NOT NULL DEFAULT 'team',
  `available` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `lastchange` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lastchangeby` int(10) UNSIGNED NULL DEFAULT NULL,
  `metadata` json NULL DEFAULT NULL,
  `state` int(10) UNSIGNED NOT NULL DEFAULT 1,
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

--
-- Table structure for table `items_comments`
--

CREATE TABLE `items_comments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
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
-- Table structure for table `items_revisions`
--

CREATE TABLE `items_revisions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` int(10) UNSIGNED NOT NULL,
  `body` mediumtext NOT NULL,
  `savedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `items_revisions`:
--

-- --------------------------------------------------------

--
-- Table structure for table `items_types`
--

CREATE TABLE `items_types` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `team` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `color` varchar(6) DEFAULT '29aeb9',
  `body` text NULL DEFAULT NULL,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  `bookable` tinyint(1) UNSIGNED DEFAULT '0',
  `canread` varchar(255) NOT NULL DEFAULT 'team',
  `canwrite` varchar(255) NOT NULL DEFAULT 'team',
  `lastchange` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lastchangeby` int(10) UNSIGNED NULL DEFAULT NULL,
  `metadata` json NULL DEFAULT NULL,
  `state` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `items_types`:
--   `team`
--       `teams` -> `id`
--

--
-- Table structure for table `items_types_links`
--

CREATE TABLE `items_types_links` (
  `item_id` int UNSIGNED NOT NULL,
  `link_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`item_id`, `link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items_types_steps`
--

CREATE TABLE `items_types_steps` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` int UNSIGNED NOT NULL,
  `body` text NOT NULL,
  `ordering` int UNSIGNED DEFAULT NULL,
  `finished` tinyint(1) NOT NULL DEFAULT '0',
  `finished_time` datetime DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `deadline_notif` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

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
  `send_email` tinyint(1) NOT NULL DEFAULT '0',
  `email_sent` tinyint(1) NOT NULL DEFAULT '0',
  `email_sent_at` datetime DEFAULT NULL,
  `is_ack` tinyint(1) NOT NULL DEFAULT '0',
  `body` json DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;


--
-- Table structure for table `pin2users`
--

CREATE TABLE `pin2users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `users_id` int(10) UNSIGNED NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `team` int(10) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `color` varchar(6) NOT NULL,
  `is_timestampable` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `is_default` tinyint(1) UNSIGNED DEFAULT NULL,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `status`:
--   `team`
--       `teams` -> `id`
--

-- --------------------------------------------------------

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
  PRIMARY KEY (`id`)
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
  `common_template` text,
  `deletable_xp` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `deletable_item` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `user_create_tag` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `force_exp_tpl` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `link_name` text NOT NULL,
  `link_href` text NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `orgid` varchar(255) DEFAULT NULL,
  `public_db` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `force_canread` varchar(255) NOT NULL DEFAULT 'team',
  `force_canwrite` varchar(255) NOT NULL DEFAULT 'user',
  `do_force_canread` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `do_force_canwrite` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `visible` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
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
  `start` varchar(255) NOT NULL,
  `end` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  `experiment` int(10) UNSIGNED DEFAULT NULL,
  `item_link` int(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

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
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `real_name` text NOT NULL,
  `long_name` text NOT NULL,
  `comment` text NOT NULL,
  `item_id` int(10) UNSIGNED DEFAULT NULL,
  `userid` text NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(128) DEFAULT NULL,
  `hash_algorithm` varchar(10) DEFAULT NULL,
  `storage` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `filesize` int(10) UNSIGNED NULL DEFAULT NULL,
  `state` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `uploads`:
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `salt` varchar(255) NULL DEFAULT NULL,
  `password` varchar(255) NULL DEFAULT NULL,
  `password_hash` varchar(255) NULL DEFAULT NULL,
  `mfa_secret` varchar(32) DEFAULT NULL,
  `usergroup` tinyint(1) UNSIGNED NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(127) DEFAULT NULL,
  `cellphone` varchar(127) DEFAULT NULL,
  `skype` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `register_date` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `limit_nb` tinyint(255) UNSIGNED NOT NULL DEFAULT '15',
  `sc_create` varchar(1) NOT NULL DEFAULT 'c',
  `sc_edit` varchar(1) NOT NULL DEFAULT 'e',
  `sc_submit` varchar(1) NOT NULL DEFAULT 's',
  `sc_todo` varchar(1) NOT NULL DEFAULT 't',
  `show_team` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `show_team_templates` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `show_public` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `use_isodate` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `uploads_layout` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `chem_editor` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `validated` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `lang` varchar(5) NOT NULL DEFAULT 'en_GB',
  `default_read` varchar(255) NULL DEFAULT 'team',
  `default_write` varchar(255) NULL DEFAULT 'user',
  `single_column_layout` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `cjk_fonts` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `orderby` varchar(255) NOT NULL DEFAULT 'date',
  `sort` varchar(255) NOT NULL DEFAULT 'desc',
  `use_markdown` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `use_ove` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `inc_files_pdf` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `append_pdfs` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `archived` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `pdfa` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `pdf_format` varchar(255) NOT NULL DEFAULT 'A4',
  `display_size` varchar(2) NOT NULL DEFAULT 'lg',
  `display_mode` VARCHAR(2) NOT NULL DEFAULT 'it',
  `last_login` DATETIME NULL DEFAULT NULL,
  `allow_untrusted` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `notif_comment_created` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `notif_comment_created_email` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `notif_user_created` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `notif_user_created_email` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `notif_user_need_validation` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `notif_user_need_validation_email` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `notif_step_deadline` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `notif_step_deadline_email` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `notif_event_deleted` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `notif_event_deleted_email` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `auth_lock_time` datetime DEFAULT NULL,
  `auth_service` tinyint(1) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- RELATIONSHIPS FOR TABLE `users`:
--   `usergroup`
--       `groups` -> `id`
--

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

CREATE TABLE `users2teams` (
  `users_id` int(10) UNSIGNED NOT NULL,
  `teams_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`users_id`, `teams_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;
--
-- RELATIONSHIPS FOR TABLE `users2teams`:
--   `teams_id`
--       `teams` -> `id`
--   `users_id`
--       `users` -> `userid`
--

--
-- Indexes for dumped tables
--

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
  ADD KEY `fk_experiments_status_id` (`category`);

--
-- Indexes for table `experiments_comments`
--
ALTER TABLE `experiments_comments`
  ADD KEY `fk_experiments_comments_experiments_id` (`item_id`),
  ADD KEY `fk_experiments_comments_users_userid` (`userid`);

--
-- Indexes for table `experiments_links`
--
ALTER TABLE `experiments_links`
  ADD KEY `fk_experiments_links_experiments_id` (`item_id`),
  ADD KEY `fk_experiments_links_items_id` (`link_id`);

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
  ADD KEY `fk_experiments_templates_users_userid` (`userid`);

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
  ADD KEY `fk_items_items_types_id` (`category`),
  ADD KEY `fk_items_users_userid` (`userid`);

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
  ADD KEY `idx_items_types_state` (`state`);

--
-- Indexes for table `items_types_links`
--
ALTER TABLE `items_types_links`
  ADD KEY `fk_items_types_links_items_id` (`item_id`),
  ADD KEY `fk_items_types_links_items_types_id` (`link_id`);

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
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD KEY `fk_status_teams_team_id` (`team`);

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD KEY `fk_users_groups_id` (`usergroup`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD CONSTRAINT `fk_api_keys_users_id` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_api_keys_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments`
--
ALTER TABLE `experiments`
  ADD CONSTRAINT `fk_experiments_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_status_id` FOREIGN KEY (`category`) REFERENCES `status` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments_comments`
--
ALTER TABLE `experiments_comments`
  ADD CONSTRAINT `fk_experiments_comments_experiments_id` FOREIGN KEY (`item_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_comments_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments_links`
--
ALTER TABLE `experiments_links`
  ADD CONSTRAINT `fk_experiments_links_experiments_id` FOREIGN KEY (`item_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_links_items_id` FOREIGN KEY (`link_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments_revisions`
--
ALTER TABLE `experiments_revisions`
  ADD CONSTRAINT `fk_experiments_revisions_experiments_id` FOREIGN KEY (`item_id`) REFERENCES `experiments`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_revisions_users_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments_steps`
--
ALTER TABLE `experiments_steps`
  ADD CONSTRAINT `fk_experiments_steps_experiments_id` FOREIGN KEY (`item_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments_templates`
--
ALTER TABLE `experiments_templates`
  ADD CONSTRAINT `fk_experiments_templates_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_templates_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `experiments_templates_revisions`
--
ALTER TABLE `experiments_templates_revisions`
  ADD CONSTRAINT `fk_experiments_templates_revisions_experiments_templates_id` FOREIGN KEY (`item_id`) REFERENCES `experiments_templates`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_experiments_templates_revisions_users_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT `fk_items_items_types_id` FOREIGN KEY (`category`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items_comments`
--
ALTER TABLE `items_comments`
  ADD CONSTRAINT `fk_items_comments_items_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_comments_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items_revisions`
--
ALTER TABLE `items_revisions`
  ADD CONSTRAINT `fk_items_revisions_items_id` FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE cascade ON UPDATE cascade,
  ADD CONSTRAINT `fk_items_revisions_users_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE cascade ON UPDATE cascade;

--
-- Constraints for table `items_types`
--
ALTER TABLE `items_types`
  ADD CONSTRAINT `fk_items_types_teams_id` FOREIGN KEY (`team`) REFERENCES `teams`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items_types_links`
--
ALTER TABLE `items_types_links`
  ADD CONSTRAINT `fk_items_types_links_items_id` FOREIGN KEY (`link_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_types_links_items_types_id` FOREIGN KEY (`item_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items_types_steps`
--
ALTER TABLE `items_types_steps`
  ADD CONSTRAINT `fk_items_types_steps_items_id` FOREIGN KEY (`item_id`) REFERENCES `items_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_users_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `status`
--
ALTER TABLE `status`
  ADD CONSTRAINT `fk_status_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tags`
--
ALTER TABLE `tags`
  ADD CONSTRAINT `fk_tags_teams_id` FOREIGN KEY (`team`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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

-- schema 49
CREATE TABLE `items_steps` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `item_id` int(10) unsigned NOT NULL,
    `body` text NOT NULL,
    `ordering` int(10) unsigned DEFAULT NULL,
    `finished` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
    `finished_time` datetime DEFAULT NULL,
    `deadline` datetime DEFAULT NULL,
    `deadline_notif` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `fk_items_steps_items_id` (`item_id`),
    CONSTRAINT `fk_items_steps_items_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE `experiments_templates_steps` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `item_id` int(10) unsigned NOT NULL,
    `body` text NOT NULL,
    `ordering` int(10) unsigned DEFAULT NULL,
    `finished` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
    `finished_time` datetime DEFAULT NULL,
    `deadline` datetime DEFAULT NULL,
    `deadline_notif` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `fk_experiments_templates_steps_items_id` (`item_id`),
    CONSTRAINT `fk_experiments_templates_steps_items_id` FOREIGN KEY (`item_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE `items_links` (
    `item_id` int(10) unsigned NOT NULL,
    `link_id` int(10) unsigned NOT NULL,
    PRIMARY KEY (`item_id`, `link_id`),
    KEY `fk_items_links_items_id` (`item_id`),
    KEY `fk_items_links_items_id2` (`link_id`),
    CONSTRAINT `fk_items_links_items_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_items_links_items_id2` FOREIGN KEY (`link_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE `experiments_templates_links` (
    `item_id` int(10) unsigned NOT NULL,
    `link_id` int(10) unsigned NOT NULL,
    PRIMARY KEY (`item_id`, `link_id`),
    KEY `fk_experiments_templates_links_items_id` (`item_id`),
    KEY `fk_experiments_templates_links_items_id2` (`link_id`),
    CONSTRAINT `fk_experiments_templates_links_experiments_templates_id` FOREIGN KEY (`item_id`) REFERENCES `experiments_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_experiments_templates_links_items_id` FOREIGN KEY (`link_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci;

--
-- Indexes and Constraints for table `users2teams`
--
ALTER TABLE `users2teams`
  ADD KEY `fk_users2teams_teams_id` (`teams_id`),
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
-- Indexes for table `pin2users`
--
ALTER TABLE `pin2users`
  ADD KEY `fk_pin2users_userid` (`users_id`);

--
-- Constraints for table `pin2users`
--
ALTER TABLE `pin2users`
  ADD CONSTRAINT `fk_pin2users_userid` FOREIGN KEY (`users_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_groups_id` FOREIGN KEY (`usergroup`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
