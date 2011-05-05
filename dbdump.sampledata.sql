# Sequel Pro dump
# Version 2492
# http://code.google.com/p/sequel-pro
#
# Host: 127.0.0.1 (MySQL 5.5.10-log)
# Database: sp1
# Generation Time: 2011-05-03 21:18:15 +0200
# ************************************************************

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table pm_comments
# ------------------------------------------------------------

LOCK TABLES `pm_comments` WRITE;
/*!40000 ALTER TABLE `pm_comments` DISABLE KEYS */;
INSERT INTO `pm_comments` (`commentId`,`comment`,`public`)
VALUES
	(1,'Upraven popis problému',0),
	(2,'Chtělo by to tu mít také nějaký další komentář',0);

/*!40000 ALTER TABLE `pm_comments` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table pm_projects
# ------------------------------------------------------------



# Dump of table pm_tickets
# ------------------------------------------------------------

LOCK TABLES `pm_tickets` WRITE;
/*!40000 ALTER TABLE `pm_tickets` DISABLE KEYS */;
INSERT INTO `pm_tickets` (`ticketId`,`revision`,`author`,`commentId`,`name`,`description`,`timestamp`)
VALUES
	(1,-3,2,2,'První ticket','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed ac risus a massa scelerisque accumsan. Quisque luctus felis eu justo semper ultrices.\n\nNunc rhoncus leo cursus erat lacinia adipiscing. Sed a lectus magna. Praesent quam tellus, commodo a lobortis placerat, vulputate vel dolor.','2011-04-28 17:29:31'),
	(1,-2,1,1,'Prnví ticket','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed ac risus a massa scelerisque accumsan. Quisque luctus felis eu justo semper ultrices.\n\nNunc rhoncus leo cursus erat lacinia adipiscing. Sed a lectus magna. Praesent quam tellus, commodo a lobortis placerat, vulputate vel dolor.','2011-04-27 13:58:11'),
	(1,-1,1,NULL,'První ticket','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed ac risus a massa scelerisque accumsan. Quisque luctus felis eu justo semper ultrices.','2011-04-27 13:57:33'),
	(1,4,1,NULL,'Bla bla ticket','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed ac risus a massa scelerisque accumsan. Quisque luctus felis eu justo semper ultrices.\n\nNunc rhoncus leo cursus erat lacinia adipiscing. Sed a lectus magna. Praesent quam tellus, commodo a lobortis placerat, vulputate vel dolor.','2011-04-28 17:29:23');

/*!40000 ALTER TABLE `pm_tickets` ENABLE KEYS */;
UNLOCK TABLES;





/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
