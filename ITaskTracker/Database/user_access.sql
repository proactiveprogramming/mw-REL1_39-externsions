# MySQL-Front 5.1  (Build 4.13)

/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE */;
/*!40101 SET SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES */;
/*!40103 SET SQL_NOTES='ON' */;


# Host: 10.153.66.92:3304    Database: BetaALwiki
# ------------------------------------------------------
# Server version 5.5.5-10.2.30-MariaDB-10.2.30+maria~xenial-log

USE `BetaALwiki`;

#
# Source for table user_access
#

CREATE TABLE `user_access` (
  `user_id` int(10) unsigned NOT NULL,
  `themes` varchar(100) NOT NULL,
  `access` varchar(2) NOT NULL,
  `tag_name` varchar(100) NOT NULL,
  `permission` varchar(100) NOT NULL,
  `last_sync` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`,`themes`,`permission`),
  KEY `themes` (`themes`,`tag_name`),
  KEY `permission` (`permission`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
