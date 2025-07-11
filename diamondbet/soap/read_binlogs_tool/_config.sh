#! /usr/bin/env bash

########## MAIN CONFIGURATIONS START ################
# directory path where binlogs files to be treated are stored
# i.e. /var/www/videoslots/diamondbet/soap/read_binlogs_tool/binlogs_files
dir=/var/www/videoslots/diamondbet/soap/read_binlogs_tool/binlogs_files
## timestamps filters
start_datetime="2022-10-03 06:30:01" 
stop_datetime="2022-10-04 10:02:48"

########## TARGET DATABASE CONFIG #############
# if target service database name is different from standard videoslots
rewrite_db='videoslots->vs_data_issues_devshard1'
# database credentials
user=lxcuser 
password=lxcpass
database=vs_data_issues_devshard1

########## MAIN CONFIGURATIONS END ################