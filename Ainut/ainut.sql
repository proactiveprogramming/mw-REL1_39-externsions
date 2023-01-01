CREATE TABLE /*$wgDBprefix*/ainut_app (
  aia_id int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  aia_timestamp varchar(14) binary,
  aia_user int unsigned NOT NULL,
  aia_code varchar(10) binary NOT NULL,
  aia_revision int unsigned NOT NULL,
  aia_value mediumblob NOT NULL
) /*$wgDBTableOptions*/;

CREATE TABLE /*$wgDBprefix*/ainut_rev (
  air_id int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  air_timestamp varchar(14) binary,
  air_user int unsigned NOT NULL,
  air_aia int unsigned NOT NULL,
  air_value mediumblob NOT NULL
) /*$wgDBTableOptions*/;
