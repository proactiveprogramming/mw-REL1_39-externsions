-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: extensions/WikibaseQualityConstraints/sql/tables.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/wbqc_constraints (
  constraint_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  constraint_guid BLOB NOT NULL, pid INTEGER NOT NULL,
  constraint_type_qid BLOB NOT NULL,
  constraint_parameters CLOB DEFAULT NULL
);

CREATE INDEX wbqc_constraints_pid_index ON /*_*/wbqc_constraints (pid);

CREATE UNIQUE INDEX wbqc_constraints_guid_uniq ON /*_*/wbqc_constraints (constraint_guid);