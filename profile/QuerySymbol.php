<?php
/**
 * Command-line tool to invoke \SameAsLite\Store->querySymbol using
 * a MySQL data store.
 *
 * Usage:
 * <pre>
 * $ php QuerySymbol.php DSN TABLE USER PASSWORD DB SYMBOL
 * </pre>
 * where:
 * - DSN - database connection URL.
 * - TABLE - table name.
 * - USER - user name.
 * - PASSWORD - password.
 * - DB - database name.
 * - SYMBOL - a symbol to request from the sameAs Lite data store.
 *
 * Example:
 * <pre>
 * $ php QuerySymbol.php 'mysql:host=127.0.0.1;port=3306;charset=utf8' table1 testuser testpass testdb http.51011a3008ce7eceba27c629f6d0020c
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

$dsn = $argv[1];
$table = $argv[2];
$user = $argv[3];
$pass = $argv[4];
$db = $argv[5];
$symbol = $argv[6];

$store = new \SameAsLite\MySqlStore($dsn, $table, $user, $pass, $db);
$result = $store->querySymbol($symbol);
foreach ($result as $r)
{
    print_r($r . PHP_EOL);
}
?>
