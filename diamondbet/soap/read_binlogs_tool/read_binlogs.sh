#! /usr/bin/env bash
# Unset the following for debugging
#set -x
########################################################################### 
# This script import timestamp filtered data from specific tables stored within
# a set of MariaDB's binlogs files.
# Data will be copied on extra migration table at the end of the process.
# @author: Giancarlo Panarese
#
# sampe of use
# . read_binlogs.sh cash_transactions
###########################################################################

source ./_config.sh
source ./_functions.sh

table=$1
desttable="migrate_$table"
echo "ready to create $desttable and looping binlogs"

_truncate_migration_table $desttable

echo "readying files..."
for f in "$dir"/mysql-bin.*; do
   newfile=$(basename "${f}")
   _log "dump ${table} from file ${newfile}"
   mysqlbinlog --start-datetime="${start_datetime}" --stop-datetime="${stop_datetime}" --rewrite-db="${rewrite_db}" --table ${table} ${dir}/${newfile} | mysql --force -u $user -p$password -D $database
done

sleep 5
_create_migration_table $table
echo "done"
