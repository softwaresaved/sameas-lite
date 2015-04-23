<?php

/**
 * Command-line tool to invoke \SameAsLite|MySqlDataCreator.
 *
 * Usage:
 * <pre>
 * $ php CreateMySqlData.php DSN TABLE USER PASSWORD DB N
 * </pre>
 * where:
 * - DSN - database connection URL.
 * - TABLE - table name.
 * - USER - user name.
 * - PASSWORD - password.
 * - DB - database name.
 * - N - number of canons and symbols per canon.
 *
 * Example:
 * <pre>
 * $ php CreateMySqlData.php 'mysql:host=127.0.0.1;port=3306;charset=utf8' table1 testuser testpass testdb 100
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

$dsn = $argv[1];
$table = $argv[2];
$user = $argv[3];
$password = $argv[4];
$db = $argv[5];
$count = intval($argv[6]);

\SameAsLite\MySqlDataCreator::create($dsn, $table, $user, $password, $db, $count);
?>
