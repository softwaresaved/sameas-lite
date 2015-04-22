<?php

/**
 * Simple cURL GET profiler.
 *
 * Calculate time, in milliseconds, to invoke a cURL HTTP GET request.
 * This operation can be run one or more times.
 * Simple validation is supported so that execution can prematurely
 * terminate if an error arises, an HTTP response code not equal to 200 
 * is returned or if the response contains "<h2>Not Found</h2>", during
 * any iteration.
 * The results returned are also appended to a get_curl.log file
 * with each batch prefixed by the iteration number and each symbol
 * on a new line.
 *
 * Usage:
 * <pre>
 * $ php get_curl.php URL [COUNT]
 * </pre>
 * where:
 * - URL - the URL to issue HTTP GET requests to.
 * - COUNT - number of iterations. Default 1.
 *
 * Example:
 * <pre>
 * $ php get_symbol.php http://127.0.0.1/sameas-lite/datasets/test/symbols/http.51011a3008ce7eceba27c629f6d0020c 10
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

$LOG_FILE="get_curl.log";
if (file_exists($LOG_FILE))
{
    unlink($LOG_FILE);
}

$url = $argv[1];
$iterations = 1;
if (count($argv) > 2)
{
    $iterations = intval($argv[2]);
}
for ($i = 0; $i < $iterations; $i++) 
{
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
 
    $start = microtime(true);
    $result = curl_exec($session);
    $end = microtime(true);

    $errno = curl_errno($session);
    $error = curl_error($session);
    $http = curl_getinfo($session, CURLINFO_HTTP_CODE);
    curl_close($session);

    if ($errno != 0)
    {
        printf("Unexpected error. cURL error number: %d, cURL error: %s\n", 
            $errno, $error);
        print_r($result); 
        exit(1);
    }
    if ($http != 200)
    {
        printf("Unexpected HTTP code. Expected: %d. Received: %d\n",
            200, $http);
        print_r($result); 
        exit(1);
    }
    if (strpos($result, "<h2>Not Found</h2>") != false)
    {
        printf("Unexpected error. The return page contains 'Not Found'\n");
        print_r($result);
    }
    $total = $end - $start;
    printf("%.4f\n", $total);
    file_put_contents($LOG_FILE, $i . PHP_EOL, FILE_APPEND);
    file_put_contents($LOG_FILE, $result . PHP_EOL, FILE_APPEND);
}
?>
