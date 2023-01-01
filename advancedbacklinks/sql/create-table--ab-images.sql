CREATE TABLE IF NOT EXISTS /*_*/ab_images (
  abi_from int(10) unsigned NOT NULL,
  abi_from_namespace int(11) NOT NULL,
  abi_title varbinary(255) NOT NULL,
  abi_through int(10) unsigned NOT NULL,
  PRIMARY KEY (abi_from, abi_title, abi_through)
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/abi_composite ON /*_*/ab_images (abi_from_namespace, abi_title, abi_from, abi_through);
CREATE UNIQUE INDEX /*i*/abi_composite_to ON /*_*/ab_images (abi_title, abi_through, abi_from);