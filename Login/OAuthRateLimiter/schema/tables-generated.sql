-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: extensions/OAuthRateLimiter/schema/tables.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/oauth_ratelimit_client_tier (
  oarct_id INT UNSIGNED AUTO_INCREMENT NOT NULL,
  oarct_client_id VARCHAR(32) NOT NULL,
  oarct_tier_name VARCHAR(255) NOT NULL,
  UNIQUE INDEX oarct_client_id (oarct_client_id),
  PRIMARY KEY(oarct_id)
) /*$wgDBTableOptions*/;