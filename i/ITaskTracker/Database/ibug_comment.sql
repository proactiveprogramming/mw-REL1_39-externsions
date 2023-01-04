# MySQL-Front 5.1  (Build 4.13)

/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE */;
/*!40101 SET SQL_MODE='' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES */;
/*!40103 SET SQL_NOTES='ON' */;


# Host: 10.153.62.13    Database: ALwikiDBdev
# ------------------------------------------------------
# Server version 5.5.5-10.2.29-MariaDB-10.2.29+maria~xenial

USE `ALwikiDBdev`;

#
# Source for table ibug_comment
#

CREATE TABLE `ibug_comment` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `ibug_id` int(10) NOT NULL,
  `user_name` varchar(25) COLLATE latin1_bin NOT NULL,
  `comment` text CHARACTER SET latin1 NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ibug_id` (`ibug_id`,`user_name`,`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
