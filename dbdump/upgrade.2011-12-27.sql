-- Adminer 3.3.1 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `pm_comments`;
CREATE TABLE `pm_comments` (
  `commentId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `comment` text NOT NULL,
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `deadlineThen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`commentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- 2011-12-27 21:34:09
