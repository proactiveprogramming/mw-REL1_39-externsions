-- This file is automatically generated using maintenance/generateSchemaChangeSql.php.
-- Source: extensions/Wikibase/repo/sql/abstractSchemaChanges/patch-wb_id_counters-unique-to-pk.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
DROP  INDEX wb_id_counters_type ON  /*_*/wb_id_counters;
ALTER TABLE  /*_*/wb_id_counters
ADD  PRIMARY KEY (id_type);