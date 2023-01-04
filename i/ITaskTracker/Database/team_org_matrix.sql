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
# Source for table team_org_matrix
#

CREATE TABLE `team_org_matrix` (
  `tag_name` varchar(25) COLLATE latin1_bin NOT NULL,
  `theme` varchar(50) COLLATE latin1_bin NOT NULL,
  `colour` varchar(10) COLLATE latin1_bin NOT NULL,
  `deleted` int(1) NOT NULL,
  `deprecated` int(1) NOT NULL,
  PRIMARY KEY (`tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
