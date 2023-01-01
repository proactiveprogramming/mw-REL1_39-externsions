-- Database definition for Expiry
--
-- Part of BlueSpice MediaWiki
--
-- @author     Sebastian Ulbricht <sebastian.ulbricht@dragon-network.hk>
-- @package    BlueSpiceExpiry
-- @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
-- @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
-- @filesource

CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/bs_expiry (
  `exp_id`      int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `exp_page_id` INT(10) NOT NULL ,
  `exp_date`    DATE NOT NULL,
  `exp_comment` VARBINARY(255)
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/ `exp_page_id_idx` ON /*_*/bs_expiry (`exp_page_id` ASC) ,
CREATE INDEX /*i*/ `exp_date_idx` ON /*_*/bs_expiry (`exp_date` ASC)
