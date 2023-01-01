CREATE TABLE /*_*/opauth_login (
  user_id int(11) NOT NULL AUTO_INCREMENT,
  provider varchar(255) DEFAULT NULL,
  uid varchar(255) DEFAULT NULL,
  PRIMARY KEY (user_id)
) /*$wgDBTableOptions*/;