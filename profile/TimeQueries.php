<?php

/**
 * Command-line tool to invoke \SameAsLite\MySqlQuerySymbolTimer and
 * \SameAsLite\CurlGetTimer. The tool:
 * - Populates MySQL table with pseudo-random data using
 *   \SameAsLite\MySqlDataCreator.
 * - Picks two canon-symbol pairs from the data.
 * - For each Timer:
 *   - Queries for each canon and symbol in these pairs
 *     using the Timer N times.
 *   - Calculates the sum, average, standard deviation,
 *     minimum and maximum for the N times (in seconds) and prints these.
 * As each canon and symbol is requested N times, N * 4 times for
 * each Timer is collected.
 * The tool outputs the following files:
 * - querySymbolData.dat - MySqlQuerySymbolTimer output data.
 * - querySymbolTimes.dat - MySqlQuerySymbolTimer times for each invocation.
 * - curlGetData.dat - CurlGetTimer output data.
 * - curlGetTimes.dat - CurlGetTimer times for each invocation.
 *
 * Usage:
 * <pre>
 * $ php TimeQueries.php DSN TABLE USER PASSWORD DB URL CANONS N
 * </pre>
 * where:
 * - DSN - database connection URL.
 * - TABLE - table name.
 * - USER - user name.
 * - PASSWORD - password.
 * - DB - database name.
 * - URL - prefix of URL to issue GET request to. This is assumed not
 *   to have a trailing "/".
 * - CANONS - number of canons, and symbols per canon to create.
 * - N - number of iterations.
 *
 * Example:
 * <pre>
 * $ php TimeQueries.php 'mysql:host=127.0.0.1;port=3306;charset=utf8' table1 testuser testpass testdb  http://127.0.0.1/sameas-lite/datasets/test/symbols 100 10
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
require_once 'profile/MySqlDataCreator.php';
require_once 'profile/MySqlQuerySymbolTimer.php';
require_once 'profile/CurlGetTimer.php';

/**
 * Save an array of values into a file. Each value is appended
 * to the file on a line of its own.
 * @param array values Values to append to file.
 * @param string file File name.
 */
function saveValues($values, $file)
{
    foreach ($values as $value)
    {
        file_put_contents($file, print_r($value, TRUE) .
            PHP_EOL, FILE_APPEND);
    }
}

/**
 * Analyse values and calculate count, total, minimum, maximum,
 * average and standard deviation and print these along with a slug
 * in a string of form:
 * <pre>
 * Average,StdDev,Total,Min,Max,Count,Slug
 * </pre>
 */
function analyseValues($values, $slug)
{
    $count = count($values);
    $sum = array_sum($values);
    $average= $sum / $count;
    $min = min($values);
    $max = max($values);
    $sumSquares = array_reduce(
        $values, create_function('$x, $y', 'return $x + $y*$y;'), 0
    );
    $stdDev = pow(($sumSquares/$count - pow(($sum/$count), 2)), 0.5);
    printf("%.5f,%.5f,%.5f,%.5f,%.5f,%d,%s\n",
        $average, $stdDev, $sum, $min, $max, $count, $slug);
}

$queryDataFile="querySymbolData.dat";
$queryTimesFile="querySymbolTimes.dat";
$curlDataFile="curlGetData.dat";
$curlTimesFile="curlGetTimes.dat";

$dsn = $argv[1];
$table = $argv[2];
$user = $argv[3];
$password = $argv[4];
$db = $argv[5];
$url = $argv[6];
// Number of canons, and symbols per canon to create.
$numCanons = $argv[7];
$iterations = $argv[8];

// For a specific symbol, expect N+1 matching symbols.
$expected = $numCanons + 1;

// Create data.
\SameAsLite\MySqlDataCreator::create($dsn, $table, $user, $password,
    $db, $numCanons);

// Select two canon-symbol pairs.
$pair1 = \SameAsLite\MySqlDataCreator::getPair($numCanons,
    intval($numCanons / 2), intval($numCanons / 2));
$pair2 = \SameAsLite\MySqlDataCreator::getPair($numCanons,
    $numCanons - 1, $numCanons - 2);
// Interleave canons and symbols.
$symbols = array($pair1[0], $pair2[1], $pair1[1], $pair2[0]);

printf("Average(s),StdDev(s),Total(s),Min(s),Max(s),Count,Run\n");

$timer = new \SameAsLite\MySqlQuerySymbolTimer($dsn, $table, $user, $password, $db);
$timer->setDataFile($queryDataFile);
$times = [];
for ($i = 0; $i < $iterations; $i++)
{
    foreach ($symbols as $symbol)
    {
        $times[] = $timer->querySymbol($symbol, $expected);
    }
}
saveValues($times, $queryTimesFile);
analyseValues($times, "querySymbol");

$timer = new \SameAsLite\CurlGetTimer($url);
$timer->setDataFile($curlDataFile);
$times = [];
for ($i = 0; $i < $iterations; $i++)
{
    foreach ($symbols as $symbol)
    {
        $times[] = $timer->httpGet($symbol);
    }
}
saveValues($times, $curlTimesFile);
analyseValues($times, "curlGet");
?>
