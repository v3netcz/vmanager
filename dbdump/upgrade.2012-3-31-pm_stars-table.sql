-- Adminer 3.3.1 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `pm_stars`;
CREATE TABLE `pm_stars` (
  `starId` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `entityId` int(11) NOT NULL,
  `entity` varchar(128) CHARACTER SET utf8mb4 NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`starId`),
  UNIQUE KEY `userId_entityId_entity` (`userId`,`entityId`,`entity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

