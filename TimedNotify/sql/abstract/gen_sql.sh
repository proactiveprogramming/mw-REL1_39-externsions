#!/usr/bin/env bash

# This Shell script automatically generates SQL files from the abstract schema

BASEDIR=$(dirname "$0")
TYPES=("mysql" "postgres" "sqlite")

echo "Generating SQL from schemas"

for TYPE in "${TYPES[@]}"; do
  for FILENAME in $BASEDIR/*.json; do
    php "$BASEDIR/../../../../maintenance/generateSchemaSql.php" \
      --json "$FILENAME" \
      --sql "$BASEDIR/../$TYPE/$(basename "$FILENAME" ".json")_table.sql" \
      --type="$TYPE"
  done
done

echo "... done."
