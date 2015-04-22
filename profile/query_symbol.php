<?php

/**
 * Simple querySymbol profiler.
 *
 * Calculate time, in milliseconds, to create \SameAsLite\MySqlStore object
 * and invoke \SameAsLite\Store->querySymbol. The operations can be
 * invoked one or more times depending upon a command-line argument.
 * Simple validation is supported so that execution can prematurely
 * terminate if the expected number of symbols are not returned during
 * any iteration.
 * The symbols returned are also appended to a query_symbol.log file
 * with each batch prefixed by the iteration number and each symbol
 * on a new line.
 *
 * Usage:
 * <pre>
 * $ php query_symbol.php DSN USER PASSWORD DB TABLE SYMBOL EXPECTED [COUNT]
 * </pre>
 * where:
 * - DSN - database connection URL.
 * - USER - user name.
 * - PASSWORD - password.
 * - DB - database name.
 * - TABLE - table name.
 * - SYMBOL - a symbol to request from the sameAs Lite data store.
 * - EXPECTED - expected number of symbols for this symbol.
 * - COUNT - number of iterations. Default 1.
 *
 * Edit this file to specify the sameAs Lite data store to use.
 *
 * Example:
 * <pre>
 * $ php query_symbol.php 'mysql:host=127.0.0.1;port=3306;charset=utf8' testuser testpass testdb table1 http.51011a3008ce7eceba27c629f6d0020c 101 10
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

$LOG_FILE="query_symbol.log";
if (file_exists($LOG_FILE))
{
    unlink($LOG_FILE);
}

$dsn = $argv[1];
$user = $argv[2];
$password = $argv[3];
$db = $argv[4];
$table = $argv[5];
$symbol = $argv[6];
$expected = intval($argv[7]);
$iterations = 1;
if (count($argv) > 8)
{
    $iterations = intval($argv[8]);
}
for ($i = 0; $i < $iterations; $i++)
{
    $start = microtime(true);
    $store = new \SameAsLite\MySqlStore($dsn, $table, $user, $password, $db);
    $result = $store->querySymbol($symbol);
    $end = microtime(true);

    $actual = count($result);
    if ($actual != $expected)
    {
        error_log('Unexpected number of rows. Expected: ' . $expected 
        . '. Found: ' . $actual);
        error_log(print_r($result, TRUE));
        exit(1);
    }
    $total = $end - $start;
    printf("%.4f\n", $total);
    file_put_contents($LOG_FILE, $i . PHP_EOL, FILE_APPEND);
    foreach ($result as $symbol) {
        file_put_contents($LOG_FILE, $symbol . PHP_EOL, FILE_APPEND);
    }
}
?>
