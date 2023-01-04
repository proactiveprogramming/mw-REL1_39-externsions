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
# Source for view vw_IbugComment
#

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vw_IbugComment` AS select `ibug_comment`.`id` AS `id`,`ibug_comment`.`ibug_id` AS `ibug_id`,`ibug_comment`.`user_name` AS `user_name`,`ibug_comment`.`comment` AS `comment`,`ibug_comment`.`timestamp` AS `timestamp`,`ibug_comment`.`deleted` AS `deleted` from `ibug_comment` order by `ibug_comment`.`timestamp`;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
