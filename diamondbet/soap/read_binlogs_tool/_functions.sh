#! /usr/bin/env bash
source ./_config.sh

function _log() {
	execdate=$(date +%F)
	echo "[$(hostname)] [$(date +%F%t%X)] $1" |& tee >> "import_data_${execdate}.log"
}

function _create_migration_table(){
	table=$1
	migrate_table="migrate_${table}"
	create_tab="CREATE TABLE IF NOT EXISTS ${migrate_table} SELECT * FROM ${database}.${table};"
	mysql -u $user -p$password -D $database -e "$create_tab"
	_log "table ${migrate_table} created"
}

function _drop_migration_table(){
  table="$1"
	prefix="migrate_"
	# verifies table name prefix starts with migrate_
	if [[ $table == *"$prefix"* ]] ; then
	        drop_tab="DROP TABLE IF EXISTS ${database}.${table};"
	        mysql -u $user -p$password -D $database -e "$drop_tab"
	        _log "table ${table} dropped"
	else
	        _log "table name ${table} is missing 'migrate_' as prefix"
	fi
}

function _truncate_migration_table(){
  migrate_table="$1"
	prefix="migrate_"
	# verifies table name prefix starts with migrate_
	if [[ $migrate_table == *"$prefix"* ]] ; then
	        truncate_tab="TRUNCATE TABLE ${database}.${migrate_table};"
			if [[ $(mysql --execute "SHOW TABLES FROM ${database} LIKE '${truncate_tab}';") -gt 0 ]] ; then
				mysql -u $user -p$password -D $database -e "$truncate_tab"
				_log "table ${migrate_table} TRUNCATED"
			else
				_log "table name ${migrate_table} does not exists"
			fi
	else
	        _log "table name ${migrate_table} is missing 'migrate_' as prefix"
	fi
}
