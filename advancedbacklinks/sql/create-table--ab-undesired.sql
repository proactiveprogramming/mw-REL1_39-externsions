CREATE TABLE IF NOT EXISTS /*_*/ab_undesired (
  abd_from int(10) unsigned NOT NULL,
  abd_namespace int(11) NOT NULL,
  abd_title varbinary(255) NOT NULL,
  abd_through int(10) unsigned NOT NULL,
  PRIMARY KEY (abd_from, abd_namespace, abd_title, abd_through)
) /*$wgDBTableOptions*/;
