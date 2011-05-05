# Sequel Pro dump
# Version 2492
# http://code.google.com/p/sequel-pro
#
# Host: 127.0.0.1 (MySQL 5.5.10-log)
# Database: vbuilder_sandbox
# Generation Time: 2011-04-04 12:27:29 +0200
# ************************************************************

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table security_user_roles
# ------------------------------------------------------------

DROP TABLE IF EXISTS `security_user_roles`;

CREATE TABLE `security_user_roles` (
  `user` int(11) NOT NULL,
  `role` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

LOCK TABLES `security_user_roles` WRITE;
/*!40000 ALTER TABLE `security_user_roles` DISABLE KEYS */;
INSERT INTO `security_user_roles` (`user`,`role`)
VALUES
	(1,'Administrator'),
	(2,'Ticket user');

/*!40000 ALTER TABLE `security_user_roles` ENABLE KEYS */;
UNLOCK TABLES;

# Dump of table security_users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `security_users`;

CREATE TABLE `security_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(254) COLLATE utf8_czech_ci NOT NULL,
  `password` char(40) COLLATE utf8_czech_ci NOT NULL,
  `registrationTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `name` varchar(254) COLLATE utf8_czech_ci NOT NULL,
  `surname` varchar(254) COLLATE utf8_czech_ci NOT NULL,
  `email` varchar(254) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

LOCK TABLES `security_users` WRITE;
/*!40000 ALTER TABLE `security_users` DISABLE KEYS */;
INSERT INTO `security_users` (`id`,`username`,`password`,`registrationTime`,`name`,`surname`,`email`)
VALUES
	(1,'admin','cb91e593d13c922e2f3bdbf854e7d086213406a2','2011-02-10 18:37:03','Jan','Noha','info@v3net.cz'),
	(2,'user','bc3a31c074ba06554693c6985dade73a2974be0a','2011-04-11 00:48:31','','','');

/*!40000 ALTER TABLE `security_users` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table pm_comments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pm_comments`;

CREATE TABLE `pm_comments` (
  `commentId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `comment` text NOT NULL,
  `public` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`commentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table pm_projects
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pm_projects`;

CREATE TABLE `pm_projects` (
  `projectId` smallint(6) unsigned NOT NULL,
  `revision` smallint(6) NOT NULL DEFAULT '1',
  `author` smallint(6) unsigned DEFAULT NULL,
  `commentId` int(11) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`projectId`,`revision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Dump of table pm_tickets
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pm_tickets`;

CREATE TABLE `pm_tickets` (
  `ticketId` smallint(6) unsigned NOT NULL,
  `revision` smallint(6) NOT NULL DEFAULT '1',
  `author` smallint(6) unsigned DEFAULT NULL,
  `commentId` int(11) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ticketId`,`revision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
