-- phpMyAdmin SQL Dump
-- version 4.2.7
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 14, 2014 at 09:00 PM
-- Server version: 10.0.12-MariaDB-log
-- PHP Version: 5.5.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `elabftw2`
--

-- --------------------------------------------------------

--
-- Table structure for table `banned_users`
--

CREATE TABLE IF NOT EXISTS `banned_users` (
`id` int(10) unsigned NOT NULL,
  `ieuieuie` int(11) NOT NULL,
  `user_infos` text NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `conf_name` varchar(255) NOT NULL,
  `conf_value` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `experiments`
--

CREATE TABLE IF NOT EXISTS `experiments` (
`id` int(10) unsigned NOT NULL,
  `team` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` int(10) unsigned NOT NULL,
  `body` text,
  `status` varchar(255) NOT NULL,
  `links` varchar(255) DEFAULT NULL,
  `userid` int(10) unsigned NOT NULL,
  `elabid` varchar(255) NOT NULL,
  `locked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `lockedby` int(10) unsigned DEFAULT NULL,
  `lockedwhen` timestamp NULL DEFAULT NULL,
  `visibility` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=103 ;

-- --------------------------------------------------------

--
-- Table structure for table `experiments_comments`
--

CREATE TABLE IF NOT EXISTS `experiments_comments` (
`id` int(10) unsigned NOT NULL,
  `datetime` datetime NOT NULL,
  `exp_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `userid` int(11) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=48 ;

-- --------------------------------------------------------

--
-- Table structure for table `experiments_links`
--

CREATE TABLE IF NOT EXISTS `experiments_links` (
`id` int(10) unsigned NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `link_id` text NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=39 ;

-- --------------------------------------------------------

--
-- Table structure for table `experiments_revisions`
--

CREATE TABLE IF NOT EXISTS `experiments_revisions` (
`id` int(10) unsigned NOT NULL,
  `exp_id` int(10) unsigned NOT NULL,
  `body` text NOT NULL,
  `savedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` int(11) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=68 ;

-- --------------------------------------------------------

--
-- Table structure for table `experiments_tags`
--

CREATE TABLE IF NOT EXISTS `experiments_tags` (
`id` int(10) unsigned NOT NULL,
  `tag` varchar(255) NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `userid` int(10) unsigned NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=212 ;

-- --------------------------------------------------------

--
-- Table structure for table `experiments_templates`
--

CREATE TABLE IF NOT EXISTS `experiments_templates` (
`id` int(10) unsigned NOT NULL,
  `team` int(10) unsigned DEFAULT NULL,
  `body` text,
  `name` varchar(255) NOT NULL,
  `userid` int(10) unsigned DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=31 ;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
`group_id` int(10) unsigned NOT NULL,
  `group_name` text NOT NULL,
  `is_sysadmin` tinyint(1) NOT NULL,
  `is_admin` text NOT NULL,
  `can_lock` text NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE IF NOT EXISTS `items` (
`id` int(10) unsigned NOT NULL,
  `team` int(10) unsigned NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `date` int(10) unsigned NOT NULL,
  `body` text,
  `rating` tinyint(10) DEFAULT '0',
  `type` int(10) unsigned NOT NULL,
  `locked` tinyint(3) unsigned DEFAULT NULL,
  `userid` int(10) unsigned NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=330 ;

-- --------------------------------------------------------

--
-- Table structure for table `items_tags`
--

CREATE TABLE IF NOT EXISTS `items_tags` (
`id` int(10) unsigned NOT NULL,
  `tag` varchar(255) NOT NULL,
  `item_id` int(10) unsigned NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=55 ;

-- --------------------------------------------------------

--
-- Table structure for table `items_types`
--

CREATE TABLE IF NOT EXISTS `items_types` (
`id` int(10) unsigned NOT NULL,
  `team` int(10) unsigned NOT NULL,
  `name` text NOT NULL,
  `bgcolor` varchar(6) DEFAULT '000000',
  `template` text
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE IF NOT EXISTS `status` (
`id` int(10) unsigned NOT NULL,
  `team` int(10) unsigned NOT NULL,
  `name` text NOT NULL,
  `color` varchar(6) NOT NULL,
  `is_default` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE IF NOT EXISTS `teams` (
`team_id` int(10) unsigned NOT NULL,
  `team_name` text NOT NULL,
  `deletable_xp` tinyint(1) NOT NULL,
  `link_name` text NOT NULL,
  `link_href` text NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE IF NOT EXISTS `uploads` (
`id` int(10) unsigned NOT NULL,
  `real_name` text NOT NULL,
  `long_name` text NOT NULL,
  `comment` text NOT NULL,
  `item_id` int(10) unsigned DEFAULT NULL,
  `userid` text NOT NULL,
  `type` varchar(255) NOT NULL,
  `date` date DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=76 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
`userid` int(10) unsigned NOT NULL,
  `username` varchar(255) NOT NULL,
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
  `can_lock` int(1) NOT NULL DEFAULT '0',
  `register_date` bigint(20) unsigned NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `theme` varchar(30) NOT NULL DEFAULT 'default',
  `display` varchar(10) NOT NULL DEFAULT 'default',
  `order_by` varchar(255) NOT NULL DEFAULT 'date',
  `sort_by` varchar(4) NOT NULL DEFAULT 'desc',
  `limit_nb` tinyint(255) NOT NULL DEFAULT '15',
  `sc_create` varchar(1) NOT NULL DEFAULT 'c',
  `sc_edit` varchar(1) NOT NULL DEFAULT 'e',
  `sc_submit` varchar(1) NOT NULL DEFAULT 's',
  `sc_todo` varchar(1) NOT NULL DEFAULT 't',
  `close_warning` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `validated` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=56 ;

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
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
 ADD PRIMARY KEY (`userid`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- ELABFTW
/* the default item_types */
INSERT INTO `items_types` (`team`, `id`, `name`, `bgcolor`, `template`) VALUES
(1, 1, 'Antibody', '31a700', '<p><strong>Host :</strong></p>\r\n<p><strong>Target :</strong></p>\r\n<p><strong>Dilution to use :</strong></p>\r\n<p>Don''t forget to add the datasheet !</p>'),
(1, 2, 'Plasmid', '29AEB9', '<p><strong>Concentration : </strong></p>\r\n<p><strong>Resistances : </strong></p>\r\n<p><strong>Backbone :</strong></p>\r\n<p><strong><br /></strong></p>'),
(1, 3, 'siRNA', '0064ff', '<p><strong>Sequence :</strong></p>\r\n<p><strong>Target :</strong></p>\r\n<p><strong>Concentration :</strong></p>\r\n<p><strong>Buffer :</strong></p>'),
(1, 4, 'Drugs', 'fd00fe', '<p><strong>Action :</strong> &nbsp;<strong> </strong></p>\r\n<p><strong>Concentration :</strong>&nbsp;</p>\r\n<p><strong>Use at :</strong>&nbsp;</p>\r\n<p><strong>Buffer :</strong> </p>'),
(1, 5, 'Crystal', '84ff00', '<p>Edit me</p>');

/* the default status */
INSERT INTO `status` (`team`, `id`, `name`, `color`, `is_default`) VALUES
(1, 1, 'Running', '0096ff', 1),
(1, 2, 'Success', '00ac00', 0),
(1, 3, 'Need to be redone', 'c0c0c0', 0),
(1, 4, 'Fail', 'ff0000', 0);

/* the default experiment template */
INSERT INTO `experiments_templates` (`team`, `body`, `name`, `userid`) VALUES
('1', '<p><span style=\"font-size: 14pt;\"><strong>Goal :</strong></span></p>
<p>&nbsp;</p>
<p><span style=\"font-size: 14pt;\"><strong>Procedure :</strong></span></p>
<p>&nbsp;</p>
<p><span style=\"font-size: 14pt;\"><strong>Results :</strong></span></p><p>&nbsp;</p>', 'default', 0);
/* the default team */
INSERT INTO `teams` (`team_id`, `team_name`, `deletable_xp`, `link_name`, `link_href`) VALUES
(1, 'Editme', 1, 'Wiki', 'https://github.com/NicolasCARPi/elabftw/wiki'),
/* the groups */
INSERT INTO `groups` (`group_id`, `group_name`, `is_sysadmin`, `is_admin`, `can_lock`) VALUES
(1, 'Sysadmins', 1, 1, 0),
(2, 'Admins', 0, 1, 0),
(3, 'Chiefs', 0, 1, 1),
(4, 'Users', 0, 0, 0);
