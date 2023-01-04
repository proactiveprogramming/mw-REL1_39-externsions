-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: extensions/BounceHandler/sql/tables.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/bounce_records (
  br_id INT UNSIGNED AUTO_INCREMENT NOT NULL,
  br_user_email VARCHAR(255) NOT NULL,
  br_timestamp BINARY(14) NOT NULL,
  br_reason VARCHAR(255) NOT NULL,
  INDEX br_mail_timestamp (
    br_user_email(50),
    br_timestamp
  ),
  INDEX br_timestamp (br_timestamp),
  PRIMARY KEY(br_id)
) /*$wgDBTableOptions*/;