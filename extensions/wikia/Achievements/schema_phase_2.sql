-- phpMyAdmin SQL Dump
-- version 3.3.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 18, 2010 at 12:18 PM
-- Server version: 5.1.46
-- PHP Version: 5.3.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `achievementsii`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_score`
--

DROP TABLE IF EXISTS `ach_user_score`;
CREATE TABLE IF NOT EXISTS `ach_user_score` (
  `wiki_id` int(9) signed NOT NULL,
  `user_id` int(5) unsigned NOT NULL,
  `score` int(10) unsigned NOT NULL,
  KEY `id` (`wiki_id`, `user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `ach_user_counters`;
CREATE TABLE IF NOT EXISTS `ach_user_counters` (
  `user_id` int(5) unsigned NOT NULL,
  `data` text NOT NULL,
  KEY `id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `ach_user_badges`;
CREATE TABLE IF NOT EXISTS `ach_user_badges` (
  `wiki_id` int(9) signed NOT NULL,
  `user_id` int(5) unsigned NOT NULL,
  `badge_type_id` int(10) signed NOT NULL,
  `badge_lap` tinyint(3) unsigned NOT NULL,
  `badge_level` tinyint(3) unsigned NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notified` tinyint(1) NOT NULL DEFAULT '0',
  KEY `id` (`wiki_id`, `user_id`),
  KEY `badge_count` (`wiki_id`, `badge_type_id`, `badge_lap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `ach_custom_badges`;
CREATE TABLE IF NOT EXISTS `ach_custom_badges` (
  `id` int(10) signed NOT NULL,
  `wiki_id` int(9) signed NOT NULL,
  `cat` varbinary(255),
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `type` tinyint(1) NOT NULL,
  `link` varbinary(255),
  `task` text,
  KEY `id` (`wiki_id`, `id`),
  KEY `type` (`wiki_id`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
