-- phpMyAdmin SQL Dump
-- version 4.6.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 30, 2016 at 05:40 PM
-- Server version: 10.1.14-MariaDB
-- PHP Version: 7.0.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `phpunit`
--
CREATE DATABASE IF NOT EXISTS `phpunit` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `phpunit`;

--
-- experiments_tpl_tags
--
CREATE TABLE `experiments_tpl_tags` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tag` VARCHAR(255) NOT NULL,
    `item_id` INT UNSIGNED NOT NULL,
    `userid` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
--
-- Table structure for table `experiments_steps`
--
CREATE TABLE `experiments_steps` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
            `item_id` INT UNSIGNED NOT NULL ,
            `body` TEXT NOT NULL ,
            `ordering` INT UNSIGNED NULL DEFAULT NULL ,
            `finished` TINYINT(1) NOT NULL DEFAULT '0',
            `finished_time` DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (`id`));
-- --------------------------------------------------------
--
-- Table structure for table `todolist`
--

CREATE TABLE `todolist` (
  `id` int(10) UNSIGNED NOT NULL,
  `body` text NOT NULL,
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  `userid` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `banned_users`
--

CREATE TABLE `banned_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_infos` text NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE `config` (
  `conf_name` varchar(255) NOT NULL,
  `conf_value` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`conf_name`, `conf_value`) VALUES
('admin_validate', '1'),
('ban_time', '60'),
('debug', '0'),
('lang', 'en_GB'),
('login_tries', '3'),
('mail_from', 'phpunit@mailgun.org'),
('mail_method', 'smtp'),
('proxy', ''),
('schema', '33'),
('sendmail_path', '/usr/sbin/sendmail'),
('smtp_address', 'smtp.mailgun.org'),
('smtp_encryption', 'tls'),
('smtp_password', 'def502005d96345a0fd512ad0ebad66b0c6b227c6ae2d9804ca6dc16c0f50832de54c12c2b56ca5e429145396b7e5fd71e1232bd5e7d84bb336809c7c6b50ee892fe03240ae9622c6d59cc537ef18d69eaaa7eb04bf1bd3d79ea074430a4'),
('smtp_port', '587'),
('smtp_username', 'phpunit@sandboxf6e3e991ad7f40eb830e6d1f35180d2b.mailgun.org'),
('stampcert', 'app/dfn-cert/pki.dfn.pem'),
('stamphash', 'sha256'),
('stamplogin', ''),
('stamppass', ''),
('stampprovider', 'http://zeitstempel.dfn.de/'),
('stampshare', '1'),
('saml_debug', '0'),
('saml_strict', '1'),
('saml_baseurl', NULL),
('saml_entityid', NULL),
('saml_acs_url', NULL),
('saml_acs_binding', NULL),
('saml_slo_url', NULL),
('saml_slo_binding', NULL),
('saml_nameidformat', NULL),
('saml_x509', NULL),
('saml_privatekey', NULL),
('saml_team', NULL),
('saml_email', NULL),
('saml_firstname', NULL),
('saml_lastname', NULL),
('local_login', '1'),
('local_register', '1');

-- --------------------------------------------------------

--
-- Table structure for table `experiments`
--

CREATE TABLE `experiments` (
  `id` int(10) UNSIGNED NOT NULL,
  `team` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` int(10) UNSIGNED NOT NULL,
  `body` mediumtext,
  `status` varchar(255) NOT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  `elabid` varchar(255) NOT NULL,
  `locked` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `lockedby` int(10) UNSIGNED DEFAULT NULL,
  `lockedwhen` timestamp NULL DEFAULT NULL,
  `timestamped` tinyint(1) NOT NULL DEFAULT '0',
  `timestampedby` int(11) DEFAULT NULL,
  `timestamptoken` text,
  `timestampedwhen` timestamp NULL DEFAULT NULL,
  `visibility` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `idps`
--

CREATE TABLE `idps` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `entityid` varchar(255) NOT NULL,
  `sso_url` varchar(255) NOT NULL,
  `sso_binding` varchar(255) NOT NULL,
  `slo_url` varchar(255) NOT NULL,
  `slo_binding` varchar(255) NOT NULL,
  `x509` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `experiments`
--

INSERT INTO `experiments` (`id`, `team`, `title`, `date`, `body`, `status`, `userid`, `elabid`, `locked`, `lockedby`, `lockedwhen`, `timestamped`, `timestampedby`, `timestamptoken`, `timestampedwhen`, `visibility`, `datetime`) VALUES
(1, 1, 'Untitled', 20160729, '<p><span style="font-size: 14pt;"><strong>Goal :</strong></span></p>\r\n<p>&nbsp;</p>\r\n<p><span style="font-size: 14pt;"><strong>Procedure :</strong></span></p>\r\n<p>&nbsp;</p>\r\n<p><span style="font-size: 14pt;"><strong>Results :</strong></span></p>\r\n<p>&nbsp;</p>', '1', 1, '20160729-01079f04e939ad08f44bda36c39faff65a83ef56', 0, NULL, NULL, 0, NULL, NULL, NULL, 'team', '2016-07-29 21:20:59');

-- --------------------------------------------------------

--
-- Table structure for table `experiments_comments`
--

CREATE TABLE `experiments_comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `datetime` datetime NOT NULL,
  `exp_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `userid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `experiments_links`
--

CREATE TABLE `experiments_links` (
  `id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `link_id` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `experiments_revisions`
--

CREATE TABLE `experiments_revisions` (
  `id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `body` mediumtext NOT NULL,
  `savedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `experiments_revisions`
--

INSERT INTO `experiments_revisions` (`id`, `item_id`, `body`, `savedate`, `userid`) VALUES
(1, 1, '<p><span style="font-size: 14pt;"><strong>Goal :</strong></span></p>\r\n<p>&nbsp;</p>\r\n<p><span style="font-size: 14pt;"><strong>Procedure :</strong></span></p>\r\n<p>&nbsp;</p>\r\n<p><span style="font-size: 14pt;"><strong>Results :</strong></span></p>\r\n<p>&nbsp;</p>', '2016-07-29 21:21:11', 1);

-- --------------------------------------------------------

--
-- Table structure for table `experiments_tags`
--

CREATE TABLE `experiments_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `tag` varchar(255) NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `userid` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `experiments_templates`
--

CREATE TABLE `experiments_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `team` int(10) UNSIGNED DEFAULT NULL,
  `body` text,
  `name` varchar(255) NOT NULL,
  `userid` int(10) UNSIGNED DEFAULT NULL,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  `bookable` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `experiments_templates`
--

INSERT INTO `experiments_templates` (`id`, `team`, `body`, `name`, `userid`, `ordering`) VALUES
(1, 1, '<p><span style="font-size: 14pt;"><strong>Goal :</strong></span></p>\n<p>&nbsp;</p>\n<p><span style="font-size: 14pt;"><strong>Procedure :</strong></span></p>\n<p>&nbsp;</p>\n<p><span style="font-size: 14pt;"><strong>Results :</strong></span></p><p>&nbsp;</p>', 'default', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `group_id` int(10) UNSIGNED NOT NULL,
  `group_name` text NOT NULL,
  `is_sysadmin` tinyint(1) NOT NULL,
  `is_admin` text NOT NULL,
  `can_lock` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`group_id`, `group_name`, `is_sysadmin`, `is_admin`, `can_lock`) VALUES
(1, 'Sysadmins', 1, '1', '0'),
(2, 'Admins', 0, '1', '0'),
(3, 'Chiefs', 0, '1', '1'),
(4, 'Users', 0, '0', '0');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(10) UNSIGNED NOT NULL,
  `team` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `date` int(10) UNSIGNED NOT NULL,
  `body` mediumtext,
  `rating` tinyint(10) DEFAULT '0',
  `type` int(10) UNSIGNED NOT NULL,
  `locked` tinyint(3) UNSIGNED DEFAULT NULL,
  `userid` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `team`, `title`, `date`, `body`, `rating`, `type`, `locked`, `userid`) VALUES
(1, 1, 'Database item 1', 20160729, '<p>Go to the admin panel to edit/add more items types!</p>', 0, 1, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `items_revisions`
--

CREATE TABLE `items_revisions` (
  `id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `body` mediumtext NOT NULL,
  `savedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `items_revisions`
--

INSERT INTO `items_revisions` (`id`, `item_id`, `body`, `savedate`, `userid`) VALUES
(1, 1, '<p>Go to the admin panel to edit/add more items types!</p>', '2016-07-29 21:21:22', 1);

-- --------------------------------------------------------

--
-- Table structure for table `items_tags`
--

CREATE TABLE `items_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `tag` varchar(255) NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `team_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `items_types`
--

CREATE TABLE `items_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `team` int(10) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `color` varchar(6) DEFAULT '000000',
  `template` text,
  `ordering` int(10) UNSIGNED DEFAULT NULL,
  `bookable` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `items_types`
--

INSERT INTO `items_types` (`id`, `team`, `name`, `color`, `template`, `ordering`, `bookable`) VALUES
(1, 1, 'Edit me', '32a100', '<p>Go to the admin panel to edit/add more items types!</p>', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user` text COLLATE utf8_unicode_ci,
  `body` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `type`, `datetime`, `user`, `body`) VALUES
(1, 'Warning', '2016-07-30 15:36:31', '127.0.0.1', 'Failed login attempt');

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `id` int(10) UNSIGNED NOT NULL,
  `team` int(10) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `color` varchar(6) NOT NULL,
  `is_timestampable` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) DEFAULT NULL,
  `ordering` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`id`, `team`, `name`, `color`, `is_timestampable`, `is_default`, `ordering`) VALUES
(1, 1, 'Running', '0096ff', 0, 1, NULL),
(2, 1, 'Success', '00ac00', 1, 0, NULL),
(3, 1, 'Need to be redone', 'c0c0c0', 1, 0, NULL),
(4, 1, 'Fail', 'ff0000', 1, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `team_id` int(10) UNSIGNED NOT NULL,
  `team_name` text NOT NULL,
  `deletable_xp` tinyint(1) NOT NULL DEFAULT '1',
  `link_name` text NOT NULL,
  `link_href` text NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stamplogin` text,
  `stamppass` text,
  `stampprovider` text,
  `stampcert` text,
  `stamphash` varchar(10) DEFAULT 'sha256',
  `team_orgid` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`team_id`, `team_name`, `deletable_xp`, `link_name`, `link_href`, `datetime`, `stamplogin`, `stamppass`, `stampprovider`, `stampcert`, `stamphash`) VALUES
(1, 'Editme', 1, 'Documentation', 'https://doc.elabftw.net', '2016-07-28 19:23:15', NULL, NULL, NULL, NULL, 'sha256');

-- --------------------------------------------------------

--
-- Table structure for table `team_events`
--

CREATE TABLE `team_events` (
  `id` int(10) UNSIGNED NOT NULL,
  `team` int(10) UNSIGNED NOT NULL,
  `item` int(10) UNSIGNED NOT NULL,
  `start` varchar(255) NOT NULL,
  `end` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `userid` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `team_groups`
--

CREATE TABLE `team_groups` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `team` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` int(10) UNSIGNED NOT NULL,
  `real_name` text NOT NULL,
  `long_name` text NOT NULL,
  `comment` text NOT NULL,
  `item_id` int(10) UNSIGNED DEFAULT NULL,
  `userid` text NOT NULL,
  `type` varchar(255) NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(128) DEFAULT NULL,
  `hash_algorithm` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userid` int(10) UNSIGNED NOT NULL,
  `salt` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `team` int(10) UNSIGNED NOT NULL,
  `usergroup` int(10) UNSIGNED NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(127) DEFAULT NULL,
  `cellphone` varchar(127) DEFAULT NULL,
  `skype` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `can_lock` int(1) NOT NULL DEFAULT '0',
  `register_date` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `limit_nb` tinyint(255) NOT NULL DEFAULT '15',
  `orderby` varchar(255) NULL DEFAULT NULL,
  `sort` varchar(255) NULL DEFAULT NULL,
  `sc_create` varchar(1) NOT NULL DEFAULT 'c',
  `sc_edit` varchar(1) NOT NULL DEFAULT 'e',
  `sc_submit` varchar(1) NOT NULL DEFAULT 's',
  `sc_todo` varchar(1) NOT NULL DEFAULT 't',
  `show_team` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `close_warning` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `chem_editor` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `validated` tinyint(1) NOT NULL DEFAULT '0',
  `lang` varchar(5) NOT NULL DEFAULT 'en_GB',
  `api_key` varchar(255) NULL DEFAULT NULL,
  `default_vis` varchar(255) NULL DEFAULT NULL,
  `single_column_layout` tinyint(1) NOT NULL DEFAULT 0,
  `cjk_fonts` tinyint(1) NOT NULL DEFAULT 0,
  `use_markdown` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userid`, `salt`, `password`, `team`, `usergroup`, `firstname`, `lastname`, `email`, `phone`, `cellphone`, `skype`, `website`, `can_lock`, `register_date`, `token`, `limit_nb`, `sc_create`, `sc_edit`, `sc_submit`, `sc_todo`, `close_warning`, `chem_editor`, `validated`, `lang`) VALUES
(1, 'f84cf883e2c79fd8beceacf17d0b6e9fe98083e49e5f3cf949e30efa14e08a08b9b1b1e1a2e26dfbb7efd6158ffc6f405ed4669626a784ae8d76a8ec7bcf3f1d', 'a3120de3fbce90abd63c2a8ec81ebfe4e00849c56a89e1d3d196290a4b88ed81e8829e79fe50ceae05f52d6422485d29dda2d88b4932dca7bfb8efb7cbdb3745', 1, 1, 'Php', 'UNIT', 'phpunit@yopmail.com', NULL, NULL, NULL, NULL, 0, 1469733882, '8873f66dfae374a3cce82f91621689cf', 15, 'c', 'e', 's', 't', 0, 0, 1, 'en_GB');

-- --------------------------------------------------------

--
-- Table structure for table `users2team_groups`
--

CREATE TABLE `users2team_groups` (
  `userid` int(10) UNSIGNED NOT NULL,
  `groupid` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banned_users`
--
ALTER TABLE `banned_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`conf_name`);

--
-- Indexes for table `experiments`
--
ALTER TABLE `experiments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `experiments_comments`
--
ALTER TABLE `experiments_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `experiments_links`
--
ALTER TABLE `experiments_links`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `experiments_revisions`
--
ALTER TABLE `experiments_revisions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `experiments_tags`
--
ALTER TABLE `experiments_tags`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `experiments_templates`
--
ALTER TABLE `experiments_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`group_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `items_revisions`
--
ALTER TABLE `items_revisions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `items_tags`
--
ALTER TABLE `items_tags`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `items_types`
--
ALTER TABLE `items_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`team_id`);

--
-- Indexes for table `team_events`
--
ALTER TABLE `team_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `team_groups`
--
ALTER TABLE `team_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userid`);

--
-- Indexes for table `idps`
--
ALTER TABLE `idps`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banned_users`
--
ALTER TABLE `banned_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `experiments`
--
ALTER TABLE `experiments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `experiments_comments`
--
ALTER TABLE `experiments_comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `experiments_links`
--
ALTER TABLE `experiments_links`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `experiments_revisions`
--
ALTER TABLE `experiments_revisions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `experiments_tags`
--
ALTER TABLE `experiments_tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `experiments_templates`
--
ALTER TABLE `experiments_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `group_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `items_revisions`
--
ALTER TABLE `items_revisions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `items_tags`
--
ALTER TABLE `items_tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `items_types`
--
ALTER TABLE `items_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `status`
--
ALTER TABLE `status`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `team_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `team_events`
--
ALTER TABLE `team_events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `team_groups`
--
ALTER TABLE `team_groups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `idps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- Indexes for table `todolist`
--
ALTER TABLE `todolist`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `todolist`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- elabftw
-- now let's add some stuff to the default db
-- create a second team
INSERT INTO teams (team_name, link_name, link_href) VALUES ('Tata team', 'doc', 'http://doc.example.org');
-- create a second user
INSERT INTO users(
    `email`,
    `password`,
    `firstname`,
    `lastname`,
    `team`,
    `usergroup`,
    `salt`,
    `register_date`,
    `validated`,
    `lang`
        ) VALUES (
    'tata@yopmail.com',
    'osef',
    'tata',
    'TATA',
    '2',
    '1',
    'osef',
    '1503372272',
    '1',
    'en-GB');


