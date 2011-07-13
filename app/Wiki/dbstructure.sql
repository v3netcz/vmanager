-- Adminer 3.2.1 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `wiki_articles`;
CREATE TABLE `wiki_articles` (
  `id` int(6) NOT NULL,
  `title` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `text` longtext COLLATE utf8_czech_ci NOT NULL,
  `added` int(13) NOT NULL,
  `last_modified` int(13) NOT NULL,
  `revision` int(6) NOT NULL,
  `active` int(1) NOT NULL,
  `url` varchar(256) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `authorId` int(11) NOT NULL,
  PRIMARY KEY (`id`,`revision`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `wiki_discussion`;
CREATE TABLE `wiki_discussion` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `associated_id` int(6) NOT NULL,
  `user_id` int(6) NOT NULL,
  `lft` int(9) NOT NULL,
  `rgt` int(9) NOT NULL,
  `level` int(5) NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `subject` varchar(128) COLLATE utf8_czech_ci NOT NULL,
  `date` int(15) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=41 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
