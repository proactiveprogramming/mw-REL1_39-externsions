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
# Source for table ibug_tracker
#

CREATE TABLE `ibug_tracker` (
  `issue_id` int(10) NOT NULL AUTO_INCREMENT,
  `type` varchar(15) NOT NULL DEFAULT 't_acc',
  `type1` varchar(15) DEFAULT NULL,
  `title` varchar(200) NOT NULL DEFAULT '',
  `summary` text NOT NULL,
  `status` varchar(6) NOT NULL DEFAULT 's_asa',
  `assignee` varchar(40) NOT NULL,
  `user_name` varchar(40) NOT NULL,
  `user_id` int(10) NOT NULL,
  `project_name` varchar(100) NOT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0,
  `history` text DEFAULT NULL,
  `priority_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` int(1) NOT NULL DEFAULT 3,
  `start_date` text NOT NULL,
  `target_date` text NOT NULL,
  `due_date` text NOT NULL,
  `perc_complete` text NOT NULL,
  `approv_by` varchar(200) NOT NULL,
  `targ_accom` text NOT NULL,
  `issue_delay` double NOT NULL DEFAULT 0,
  `issue_mistake` double NOT NULL DEFAULT 0,
  `issue_point` double NOT NULL DEFAULT 5,
  `owned_by` varchar(40) NOT NULL,
  `coor` varchar(25) NOT NULL,
  `Issuerndfile` varchar(40) DEFAULT NULL,
  `old_type` varchar(5) DEFAULT NULL,
  `old_status` varchar(6) DEFAULT NULL,
  `old_project_name` varchar(100) DEFAULT NULL,
  `last_modifier` varchar(40) NOT NULL,
  `reason` text DEFAULT NULL,
  `depend_id` varchar(200) DEFAULT '',
  `parent_id` int(10) DEFAULT 0,
  `parent_id1` int(10) DEFAULT NULL,
  `subID` text DEFAULT NULL,
  `autoWork` char(1) NOT NULL DEFAULT '0',
  `parent_project` text DEFAULT NULL,
  `parent_project1` text DEFAULT NULL,
  PRIMARY KEY (`issue_id`),
  KEY `user_id` (`user_id`),
  KEY `coor` (`coor`),
  KEY `owned_by` (`owned_by`),
  KEY `approv_by` (`approv_by`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY `user_name` (`user_name`),
  KEY `title` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
