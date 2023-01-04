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
# Source for table ibug_logging
#

CREATE TABLE `ibug_logging` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `issue_id` int(10) NOT NULL,
  `user_name` varchar(25) CHARACTER SET latin1 NOT NULL,
  `type` varchar(15) CHARACTER SET latin1 NOT NULL,
  `comment_id` int(10) NOT NULL DEFAULT 0,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `old_value` text CHARACTER SET latin1 NOT NULL,
  `new_value` text CHARACTER SET latin1 NOT NULL,
  `remark` text CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
