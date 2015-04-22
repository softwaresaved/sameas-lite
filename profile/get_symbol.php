<?php

/**
 * sameAs Lite get symbol client and output timing information.
 *
 * Calculate time, in milliseconds, to create \SameAsLite\Store object
 * and invoke \SameAsLite\Store->querySymbol. These operations can
 * be run 1 or more times.
 *
 * Usage:
 * <pre>
 * $ php get_symbol.php SYMBOL EXPECTED [COUNT]
 * </pre>
 * where:
 * - SYMBOL - a symbol to request from the sameAs Lite data store.
 * - EXPECTED - expected number of symbols for this symbol.
 * - COUNT - number of iterations. Default 1.
 *
 * Edit this file to specify the sameAs Lite data store to use.
 *
 * Example:
 * <pre>
 * $ php get_symbol.php http.51011a3008ce7eceba27c629f6d0020c 101
 * <pre>
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

$expected = intval($argv[2]);
$iterations = 1;
if (count($argv) > 3)
{
    $iterations = intval($argv[3]);
}
for ($i = 0; $i < $iterations; $i++) 
{
    $start = microtime(true);
    $store = new \SameAsLite\Store(
        'mysql:host=127.0.0.1;port=3306;charset=utf8',
        'table1',
        'testuser',
        'testpass',
        'testdb'
    );
    $result = $store->querySymbol($argv[1]);
    $actual = count($result);
    if ($actual != $expected)
    {
        printf("Unexpected number of rows. Expected %d. Found %d\n", $expected, $actual);
        exit(1);
    }
    $end = microtime(true);
    $total = $end - $start;
    printf("%.4f\n", $total);
}
?>
