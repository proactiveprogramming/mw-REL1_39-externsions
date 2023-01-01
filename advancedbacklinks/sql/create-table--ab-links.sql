CREATE TABLE IF NOT EXISTS /*_*/ab_links (
  abl_from int(10) unsigned NOT NULL,
  abl_from_namespace int(11) NOT NULL,
  abl_namespace int(11) NOT NULL,
  abl_title varbinary(255) NOT NULL,
  abl_through int(10) unsigned NOT NULL,
  abl_hidden_through int(10) unsigned NOT NULL,
  PRIMARY KEY (abl_from, abl_namespace, abl_title, abl_through)
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/abl_composite ON /*_*/ab_links (abl_from_namespace, abl_namespace, abl_title, abl_from, abl_through);
CREATE UNIQUE INDEX /*i*/abl_composite_to ON /*_*/ab_links (abl_namespace, abl_title, abl_through, abl_from);
CREATE INDEX /*i*/abl_composite_hidden on /*_*/ab_links (abl_hidden_through, abl_from_namespace, abl_from);