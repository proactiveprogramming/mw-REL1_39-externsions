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
# Source for table ibug_tracker_status
#

CREATE TABLE `ibug_tracker_status` (
  `status` varchar(25) COLLATE latin1_bin NOT NULL,
  `name` varchar(50) COLLATE latin1_bin NOT NULL,
  `colour` varchar(10) COLLATE latin1_bin NOT NULL,
  `deleted` int(1) NOT NULL,
  `deprecated` int(1) NOT NULL,
  `usedAdd` int(1) NOT NULL,
  `sorter` int(1) NOT NULL,
  `Gstatus` varchar(4) COLLATE latin1_bin NOT NULL,
  `JScomp` int(1) NOT NULL,
  `JSname` varchar(50) COLLATE latin1_bin NOT NULL,
  `JSusertype` varchar(50) COLLATE latin1_bin NOT NULL,
  `sorter1` int(1) NOT NULL,
  PRIMARY KEY (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_bin;

#
# Dumping data for table ibug_tracker_status
#

INSERT INTO `ibug_tracker_status` VALUES ('s_aba','Bug Assigned','fb4949',0,0,1,1,'bugw',0,'Assigned to','owned_by',3);
INSERT INTO `ibug_tracker_status` VALUES ('s_abb','Bug Working','cc66fb',0,0,1,2,'bug1',25,'Worked on by','owned_by',4);
INSERT INTO `ibug_tracker_status` VALUES ('s_abca','Bug Feedback','9b9b9b',0,0,1,3,'bugw',50,'Feedback to','approv_by',1);
INSERT INTO `ibug_tracker_status` VALUES ('s_abcb','Bug Pending Approval','fa8072',0,0,1,4,'bugw',75,'Pend apprv by','approv_by',2);
INSERT INTO `ibug_tracker_status` VALUES ('s_abcc','Bug Pending Development','D2691E',0,0,0,5,'bug2',15,'Pending by','owned_by',5);
INSERT INTO `ibug_tracker_status` VALUES ('s_abcd','Bug Pending Feedback','f0ad4e',0,0,0,0,'bug2',30,'Pend feedb by','approv_by',0);
INSERT INTO `ibug_tracker_status` VALUES ('s_abd','Bug Approved','affc80',0,0,0,6,'bug2',100,'Approved by','approv_by',6);
INSERT INTO `ibug_tracker_status` VALUES ('s_abe','Bug Cancelled','FFDFDF',0,0,1,7,'bug2',0,'Cancel by','approv_by',7);
INSERT INTO `ibug_tracker_status` VALUES ('s_ana','New Development Assigned','17d81b',0,1,0,10,'neww',0,'Assigned to','owned_by',0);
INSERT INTO `ibug_tracker_status` VALUES ('s_anb','New Development Working','fdfc92',0,1,0,11,'new1',25,'Worked on by','owned_by',0);
INSERT INTO `ibug_tracker_status` VALUES ('s_anca','New Development Feedback','fea634',0,1,0,12,'neww',50,'Feedback to','approv_by',0);
INSERT INTO `ibug_tracker_status` VALUES ('s_ancb','New Development Pending Approval','4169E1',0,1,0,13,'neww',75,'Pend apprv by','approv_by',0);
INSERT INTO `ibug_tracker_status` VALUES ('s_and','New Development Approved','b9fc91',0,1,0,14,'new2',100,'Approved by','approv_by',0);
INSERT INTO `ibug_tracker_status` VALUES ('s_ane','New Development Cancelled','FFDFDF',0,1,0,15,'new2',0,'Cancel by','approv_by',0);

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
