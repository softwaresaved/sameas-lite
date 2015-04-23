<?php

/**
 * Command-line tool to invoke \SameAsLite\MySqlQuerySymbolTimer, save
 * the query results to a file and print the execution time.
 *
 * Usage:
 * <pre>
 * $ php QuerySymbol.php DSN TABLE USER PASSWORD DB SYMBOL DATAFILE [EXPECTED]
 * </pre>
 * where:
 * - DSN - database connection URL.
 * - TABLE - table name.
 * - USER - user name.
 * - PASSWORD - password.
 * - DB - database name.
 * - SYMBOL - a symbol to request from the sameAs Lite data store.
 * - DATAFILE - file to log query results into..
 * - EXPECTED - expected number of symbols for this symbol.
 *
 * Example:
 * <pre>
 * $ php QuerySymbol.php 'mysql:host=127.0.0.1;port=3306;charset=utf8' table1 testuser testpass testdb http.51011a3008ce7eceba27c629f6d0020c query.dat 101
 * </pre>
 *
 * Copyright 2015 The University of Edinburgh
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once 'vendor/autoload.php';
require_once 'profile/MySqlQuerySymbolTimer.php';

$dsn = $argv[1];
$table = $argv[2];
$user = $argv[3];
$password = $argv[4];
$db = $argv[5];
$symbol = $argv[6];
$dataFile = $argv[7];
$expected = -1;
if (count($argv) > 8)
{
    $expected = intval($argv[8]);
}

$timer = new \SameAsLite\MySqlQuerySymbolTimer($dsn, $table, $user, $password, $db);
$timer->setDataFile($dataFile);
$total = $timer->querySymbol($symbol, $expected);
printf("%.4f\n", $total);
?>
