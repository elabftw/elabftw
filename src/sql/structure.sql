--
-- elabftw mysql structure. This file is loaded upon installation.
--
-- @author Nicolas CARPi <nicolas.carpi@curie.fr>
-- @copyright 2012 Nicolas CARPi
-- @see https://www.elabftw.net Official website
-- @license AGPL-3.0
-- @package elabftw

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET character_set_client = utf8mb4 */;

--
-- Table structure for table `banned_users`
--

DROP TABLE IF EXISTS `banned_users`;
CREATE TABLE `banned_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_infos` text NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `conf_name` varchar(100) NOT NULL,
  `conf_value` text,
  PRIMARY KEY (`conf_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- tags
--
DROP TABLE IF EXISTS `tags`;
CREATE TABLE`tags` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `team` INT NOT NULL ,
    `tag` VARCHAR(255) NOT NULL ,
    PRIMARY KEY (`id`)
);

--
-- tags2entity
--
DROP TABLE IF EXISTS `tags2entity`;
CREATE TABLE `tags2entity` ( `item_id` INT NOT NULL , `tag_id` INT NOT NULL , `item_type` VARCHAR(255) NOT NULL);

-- --------------------------------------------------------
--
-- Table structure for table `experiments_steps`
--
DROP TABLE IF EXISTS `experiments_steps`;
CREATE TABLE `experiments_steps` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
            `item_id` INT UNSIGNED NOT NULL ,
            `body` TEXT NOT NULL ,
            `ordering` INT UNSIGNED NULL DEFAULT NULL ,
            `finished` TINYINT(1) NOT NULL DEFAULT '0',
            `finished_time` DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
--
-- Table structure for table `experiments`
--

DROP TABLE IF EXISTS `experiments`;
CREATE TABLE `experiments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` int(10) unsigned NOT NULL,
  `body` mediumtext,
  `status` varchar(255) NOT NULL,
  `userid` int(10) unsigned NOT NULL,
  `elabid` varchar(255) NOT NULL,
  `locked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `lockedby` int(10) unsigned DEFAULT NULL,
  `lockedwhen` timestamp NULL DEFAULT NULL,
  `timestamped` tinyint(1) NOT NULL DEFAULT '0',
  `timestampedby` int(11) DEFAULT NULL,
  `timestamptoken` text,
  `timestampedwhen` timestamp NULL,
  `visibility` varchar(255) NOT NULL DEFAULT 'team',
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `experiments_comments`
--

DROP TABLE IF EXISTS `experiments_comments`;
CREATE TABLE `experiments_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `item_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `userid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `items_comments`
--

DROP TABLE IF EXISTS `items_comments`;
CREATE TABLE `items_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `item_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `userid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
--
-- Table structure for table `experiments_links`
--

DROP TABLE IF EXISTS `experiments_links`;
CREATE TABLE `experiments_links` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `link_id` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `experiments_revisions`
--

DROP TABLE IF EXISTS `experiments_revisions`;
CREATE TABLE `experiments_revisions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `body` mediumtext NOT NULL,
  `savedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `items_revisions`
--

DROP TABLE IF EXISTS `items_revisions`;
CREATE TABLE `items_revisions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `body` mediumtext NOT NULL,
  `savedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `experiments_templates`
--

DROP TABLE IF EXISTS `experiments_templates`;
CREATE TABLE `experiments_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team` int(10) unsigned DEFAULT NULL,
  `body` text,
  `name` varchar(255) NOT NULL,
  `userid` int(10) unsigned DEFAULT NULL,
  `ordering` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_name` text NOT NULL,
  `is_sysadmin` tinyint(1) NOT NULL,
  `is_admin` text NOT NULL,
  `can_lock` text NOT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
CREATE TABLE `items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team` int(10) unsigned NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `date` int(10) unsigned NOT NULL,
  `body` mediumtext,
  `rating` tinyint(10) DEFAULT '0',
  `type` int(10) unsigned NOT NULL,
  `locked` tinyint(3) unsigned DEFAULT NULL,
  `userid` int(10) unsigned NOT NULL,
  `visibility` varchar(255) NOT NULL DEFAULT 'team',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--
-- Table structure for table `items_types`
--

DROP TABLE IF EXISTS `items_types`;
CREATE TABLE `items_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team` int(10) unsigned NOT NULL,
  `name` text NOT NULL,
  `color` varchar(6) DEFAULT '000000',
  `template` text,
  `ordering` int(10) unsigned DEFAULT NULL,
  `bookable` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `status`
--

DROP TABLE IF EXISTS `status`;
CREATE TABLE `status` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team` int(10) unsigned NOT NULL,
  `name` text NOT NULL,
  `color` varchar(6) NOT NULL,
  `is_timestampable` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) DEFAULT NULL,
  `ordering` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
CREATE TABLE `teams` (
  `team_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team_name` text NOT NULL,
  `deletable_xp` tinyint(1) NOT NULL DEFAULT 1,
  `public_db` tinyint(1) NOT NULL DEFAULT 0,
  `link_name` text NOT NULL,
  `link_href` text NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stamplogin` text DEFAULT NULL,
  `stamppass` text DEFAULT NULL,
  `stampprovider` text DEFAULT NULL,
  `stampcert` text DEFAULT NULL,
  `stamphash` varchar(10) DEFAULT 'sha256',
  `team_orgid` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `uploads`
--

DROP TABLE IF EXISTS `uploads`;
CREATE TABLE `uploads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `real_name` text NOT NULL,
  `long_name` text NOT NULL,
  `comment` text NOT NULL,
  `item_id` int(10) unsigned DEFAULT NULL,
  `userid` text NOT NULL,
  `type` varchar(255) NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(128) DEFAULT NULL,
  `hash_algorithm` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `userid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `salt` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `team` int(10) unsigned NOT NULL,
  `usergroup` int(10) unsigned NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(127) DEFAULT NULL,
  `cellphone` varchar(127) DEFAULT NULL,
  `skype` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `register_date` bigint(20) unsigned NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `limit_nb` tinyint(255) NOT NULL DEFAULT '15',
  `orderby` varchar(255) NULL DEFAULT NULL,
  `sort` varchar(255) NULL DEFAULT NULL,
  `sc_create` varchar(1) NOT NULL DEFAULT 'c',
  `sc_edit` varchar(1) NOT NULL DEFAULT 'e',
  `sc_submit` varchar(1) NOT NULL DEFAULT 's',
  `sc_todo` varchar(1) NOT NULL DEFAULT 't',
  `show_team` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `close_warning` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `chem_editor` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `validated` tinyint(1) NOT NULL DEFAULT '0',
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  `lang` varchar(5) NOT NULL DEFAULT 'en_GB',
  `api_key` varchar(255) NULL DEFAULT NULL,
  `default_vis` varchar(255) NULL DEFAULT 'team',
  `single_column_layout` tinyint(1) NOT NULL DEFAULT 0,
  `cjk_fonts` tinyint(1) NOT NULL DEFAULT 0,
  `pdfa` tinyint(1) NOT NULL DEFAULT 1,
  `pdf_format` varchar(255) NOT NULL DEFAULT 'A4',
  `use_markdown` tinyint(1) NOT NULL DEFAULT 0,
  `allow_edit` tinyint(1) NOT NULL DEFAULT 0,
  `inc_files_pdf` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--
-- Table structure for table `team_groups`
--

CREATE TABLE IF NOT EXISTS `team_groups` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `team` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for table `team_groups`
--
ALTER TABLE `team_groups`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for table `team_groups`
--
ALTER TABLE `team_groups`
  MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `users2team_groups` (
  `userid` int(10) unsigned NOT NULL,
  `groupid` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `team_events`
--

CREATE TABLE IF NOT EXISTS `team_events` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `team` int(10) UNSIGNED NOT NULL,
  `item` int(10) UNSIGNED NOT NULL,
  `start` varchar(255) NOT NULL,
  `end` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- todolist
--

CREATE TABLE IF NOT EXISTS `todolist` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `body` text NOT NULL,
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--
-- idps
--
CREATE TABLE IF NOT EXISTS `idps` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `entityid` VARCHAR(255) NOT NULL,
  `sso_url` VARCHAR(255) NOT NULL,
  `sso_binding` VARCHAR(255) NOT NULL,
  `slo_url` VARCHAR(255) NOT NULL,
  `slo_binding` VARCHAR(255) NOT NULL,
  `x509` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

/* the groups */
INSERT INTO `groups` (`group_id`, `group_name`, `is_sysadmin`, `is_admin`, `can_lock`) VALUES
(1, 'Sysadmins', 1, 1, 0),
(2, 'Admins', 0, 1, 0),
(3, 'Chiefs', 0, 1, 1),
(4, 'Users', 0, 0, 0);
