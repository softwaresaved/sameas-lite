<?php

/**
 * Command-line tool to invoke \SameAsLite\ShelllTimer and
 * \SameAsLite\CurlGetTimer. The tool:
 * - Populates MySQL table with pseudo-random data using
 *   \SameAsLite\MySqlDataCreator.
 * - Picks two canon-symbol pairs from the data.
 * - Invokes ShellTimer to time execution of QuerySymbol.php for each
 *   canon and symbol in these pairs, N times.
 * - Invokes CurlTimer to time execution of an HTTP GET against the
 *   REST endpoint for each canon and symbol in these pairs, N times.
 * - As each canon and symbol is requested N times, N * 4 times for
 *   each timer is collected.
 * - For each timer, calculates the sum, average, standard deviation,
 *   minimum and maximum of the times and prints these.
 * The tool outputs the following files:
 * - shellData.dat - ShellTimer output data.
 * - shellTimes.dat - ShellTimer times for each invocation.
 * - curlData.dat - CurlTimer output data.
 * - curlTimes.dat - CurlTimer times for each invocation.
 *
 * Usage:
 * <pre>
 * $ php Benchmark.php DSN TABLE USER PASSWORD DB URL CANONS N
 * </pre>
 * where:
 * - DSN - database connection URL.
 * - TABLE - table name.
 * - USER - user name.
 * - PASSWORD - password.
 * - DB - database name.
 * - PHP - location of QuerySymbol PHP script.
 * - URL - prefix of URL to issue GET requests to. This is assumed not
 *   to have a trailing "/".
 * - CANONS - number of canons, and symbols per canon to create.
 * - N - number of iterations.
 *
 * Example:
 * <pre>
 * $ php profile/Benchmark.php 'mysql:host=127.0.0.1;port=3306;charset=utf8' table1 testuser testpass testdb profile/QuerySymbol.php http://127.0.0.1/sameas-lite/datasets/test/symbols 100 100
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
require_once 'profile/CurlTimer.php';
require_once 'profile/MySqlDataCreator.php';
require_once 'profile/ShellTimer.php';

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

$shellDataFile="shellData.dat";
$shellTimesFile="shellTimes.dat";
$curlDataFile="curlData.dat";
$curlTimesFile="curlTimes.dat";

$dsn = $argv[1];
$table = $argv[2];
$user = $argv[3];
$password = $argv[4];
$db = $argv[5];
$script = $argv[6];
$url = $argv[7];
// Number of canons, and symbols per canon to create.
$numCanons = $argv[8];
$iterations = $argv[9];

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

$timer = new \SameAsLite\ShellTimer();
$timer->setDataFile($shellDataFile);
$times = [];
for ($i = 0; $i < $iterations; $i++)
{
    foreach ($symbols as $symbol)
    {
        $cmd = 'php ' . $script . ' \'' . $dsn . '\' ' . $table . ' ' .
            $user . ' ' . $password . ' ' . $db . ' ' . $symbol;
        $times[] = $timer->execute($cmd);
    }
}
saveValues($times, $shellTimesFile);
analyseValues($times, "shell");

$timer = new \SameAsLite\CurlTimer($url);
$timer->setDataFile($curlDataFile);
$times = [];
for ($i = 0; $i < $iterations; $i++)
{
    foreach ($symbols as $symbol)
    {
        $symbolUrl = $url . '/' . $symbol;
        $times[] = $timer->httpGet($symbolUrl);
    }
}
saveValues($times, $curlTimesFile);
analyseValues($times, "curl");
?>
