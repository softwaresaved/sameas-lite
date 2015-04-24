<?php

/**
 * Command-line tool to invoke \SameAsLite\CurlTimer, save
 * the results to a file and print the execution time.
 *
 * Usage:
 * <pre>
 * $ php Curl.php URL [DATAFILE]
 * </pre>
 * where:
 * - URL - URL to issue GET request to.
 * - DATAFILE - Optional file to log outputs to.
 *
 * Example:
 * <pre>
 * $ php Curl.php http://127.0.0.1/sameas-lite/datasets/test/symbols/http.51011a3008ce7eceba27c629f6d0020c curl.dat
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

$url = $argv[1];
$dataFile = null;
if (count($argv) > 2)
{
    $dataFile = $argv[2];
}
$timer = new \SameAsLite\CurlTimer();
$timer->setDataFile($dataFile);
$total = $timer->httpGet($url);
printf("%.4f\n", $total);
?>
