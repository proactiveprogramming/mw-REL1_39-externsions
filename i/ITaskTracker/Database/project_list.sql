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
# Source for table project_list
#

CREATE TABLE `project_list` (
  `p_id` int(11) NOT NULL AUTO_INCREMENT,
  `p_page_id` int(10) DEFAULT NULL,
  `p_title` varbinary(255) NOT NULL DEFAULT '',
  `p_parent_list` text DEFAULT NULL,
  `p_parent_list_title` text DEFAULT NULL,
  `p_themes` text DEFAULT NULL,
  PRIMARY KEY (`p_id`),
  UNIQUE KEY `p_page_id` (`p_page_id`)
) ENGINE=InnoDB AUTO_INCREMENT=680 DEFAULT CHARSET=latin1;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
