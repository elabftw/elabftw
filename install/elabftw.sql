-- phpMyAdmin SQL Dump
-- version 3.5.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 01, 2013 at 10:45 PM
-- Server version: 5.5.29-log
-- PHP Version: 5.4.11

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `elabftwtest`
--

-- --------------------------------------------------------

--
-- Table structure for table `experiments`
--

CREATE TABLE IF NOT EXISTS `experiments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `date` int(10) unsigned NOT NULL,
  `body` text NOT NULL,
  `status` varchar(255) NOT NULL,
  `links` varchar(255) DEFAULT NULL,
  `userid` int(10) unsigned NOT NULL,
  `elabid` varchar(255) NOT NULL,
  `locked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `experiments_links`
--

CREATE TABLE IF NOT EXISTS `experiments_links` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `link_id` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `experiments_tags`
--

CREATE TABLE IF NOT EXISTS `experiments_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `userid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `experiments_templates`
--

CREATE TABLE IF NOT EXISTS `experiments_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `body` text,
  `name` varchar(255) NOT NULL,
  `userid` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE IF NOT EXISTS `items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `date` int(10) unsigned NOT NULL,
  `body` text,
  `rating` tinyint(10) DEFAULT '0',
  `type` int(10) unsigned NOT NULL,
  `userid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `items_tags`
--

CREATE TABLE IF NOT EXISTS `items_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `items_templates`
--

CREATE TABLE IF NOT EXISTS `items_templates` (
  `id` int(10) unsigned NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `body` text,
  `tags` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `items_types`
--

CREATE TABLE IF NOT EXISTS `items_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `bgcolor` varchar(6) DEFAULT '000000',
  `template` text,
  `tags` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE IF NOT EXISTS `uploads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `real_name` text NOT NULL,
  `long_name` text NOT NULL,
  `comment` text NOT NULL,
  `item_id` int(10) unsigned DEFAULT NULL,
  `userid` text NOT NULL,
  `type` varchar(255) NOT NULL,
  `date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `userid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `salt` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(127) DEFAULT NULL,
  `cellphone` varchar(127) DEFAULT NULL,
  `skype` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_jc_resp` tinyint(1) NOT NULL DEFAULT '0',
  `is_pi` tinyint(1) NOT NULL DEFAULT '0',
  `journal` int(11) NOT NULL DEFAULT '0',
  `last_jc` int(4) NOT NULL,
  `register_date` bigint(20) unsigned NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `group` varchar(255) NOT NULL DEFAULT 'user',
  `theme` varchar(30) NOT NULL DEFAULT 'default',
  `display` varchar(10) NOT NULL DEFAULT 'default',
  `order_by` varchar(255) NOT NULL DEFAULT 'date',
  `sort_by` varchar(4) NOT NULL DEFAULT 'desc',
  `limit_nb` tinyint(255) NOT NULL DEFAULT '15',
  `sc_create` varchar(1) NOT NULL DEFAULT 'c',
  `sc_edit` varchar(1) NOT NULL DEFAULT 'e',
  `sc_submit` varchar(1) NOT NULL DEFAULT 's',
  `sc_todo` varchar(1) NOT NULL DEFAULT 't',
  `validated` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- ELABFTW
-- create root user with password toor and admin rights
INSERT INTO `users` VALUES(1, 'root', 'f69483d4a0edb8cc81092a0412b7b276e2fda6fda3a16cb61d16626cb6cd39b235721093e74eb93e23c2021afb3b6056aedb1a45b5bd767baf42d592f99b4c89', '8c744dc6b145df85c03655a678657bf3096ed7b6acd76d2bb27914069f544b07ad164ddf759db02d6bd6542fa4041a04b16060431cbc55d6814f12b048f43240', 'Admin', 'ROOT', 'noreply@nodomain.net', NULL, NULL, NULL, NULL, 1, 0, 0, 0, 0, 1351677553, NULL, 'user', 'default', 'default', 'date', 'desc', 15, 'c', 'e', 's', 't', 1);
