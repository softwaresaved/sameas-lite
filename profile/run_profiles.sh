#!/bin/bash

# Simple sameAs Lite profiler.
#
# This program:
#
# - Populates MySQL table with pseudo-random data using
#   create_mysql_data.php. 
# - Queries for two canon-symbol pairs known to be in the table using:
#   - query_symbol.php 
#   - get_curl.php
# 
# Each of these PHP scripts outputs the time in seconds to run each
# query for each canon and symbol. Each canon and symbol is requested
# N times where N, the number of iterations, is specified by the user.
# As a result for each PHP scripts there are N * 4 times output.
#
# Sample usage:
#
# $ bash run_profiles.sh [N]
#
# where:
# - N - number of iterations. Default 1.
#
# Copyright 2015 The University of Edinburgh
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#   http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
# implied.
# See the License for the specific language governing permissions and
# limitations under the License.

# Given a file, calculate statistics of the first column of that file
# and output: Average,StdDev,Total,Min,Max,Count,Run
# Parameters
# - String identifier, used as Run value.
# - File to analyse.
function analyse_times {
    awk -v run=$1 'NR==1 {sum=$1;sumsq=$1*$1;min=$1;max=$1;} NR>1 {min=(min<$1)?min:$1; max=(max>$1)?max:$1; sum+=$1; sumsq+=($1*$1)} END {printf "%.5f,%.5f,%.5f,%.5f,%.5f,%d,%s\n", sum/NR, sqrt(sumsq/NR - (sum/NR)^2), sum, min, max, NR, run}' $2
}

# Database
DSN='mysql:host=127.0.0.1;port=3306;charset=utf8' 
USER=testuser
PASSWORD=testpass
DB=testdb
TABLE=table1
# Service end-point. Assumed to expose the database above.
URI=http://127.0.0.1/sameas-lite/datasets/test/symbols

# Data files
QUERY_DAT=query_symbol.dat
CURL_DAT=get_curl.dat

# Number of canons, and symbols per canon to create.
NUMCANONS=100

# create_mysql_data.php, when asked to create 100 canons and 100
# symbols per canon will, due to the random seed, create a table with
# the following canon-symbol pairs:
#
# canon                                 symbol
# http.51011a3008ce7eceba27c629f6d0020c http.f9070a2d98db3c376dcd2d4d8c0cd220
# http.e5e8c7e32278573b20b15d7349a895d1 http.f26aff24a6fd831094b2520a8a5197a3
# Interleave canons and symbols.
URLS=(
  # canon 1
  http.51011a3008ce7eceba27c629f6d0020c
  # symbol 2
  http.f26aff24a6fd831094b2520a8a5197a3
  # symbol 1
  http.f9070a2d98db3c376dcd2d4d8c0cd220
  # canon 2
  http.e5e8c7e32278573b20b15d7349a895d1
)

# Number of iterations.
if [ $# -gt 0 ]; then
  N=$1
else
  N=1
fi

# Remove files from previous runs.
rm -f $QUERY_DAT
rm -f $CURL_DAT

printf "Average(s),StdDev(s),Total(s),Min(s),Max(s),Count,Run\n"

php profile/create_mysql_data.php $DSN $USER $PASSWORD $DB $TABLE $NUMCANONS

for I in `seq 1 $N` ; do
    for SYMBOL in ${URLS[@]}; do
        php profile/query_symbol.php $DSN $USER $PASSWORD $DB $TABLE $SYMBOL $((NUMCANONS+1)) 1 >> $QUERY_DAT
    done
done
analyse_times query_symbol $QUERY_DAT

for I in `seq 1 $N` ; do
    for SYMBOL in ${URLS[@]}; do
        php profile/get_curl.php $URI/$SYMBOL 1 >> $CURL_DAT
    done
done
analyse_times get_curl $CURL_DAT
