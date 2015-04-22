<?php

/**
 * MySQL random data creator.
 *
 * Populate a database table with randomly created canons and
 * symbols. A seed is used so the random values created each time are
 * the same. If the table does not exist it is created, if it does
 * exist it is first emptied.
 *
 * Usage:
 * <pre>
 * $ php create_data.php DSN USER PASSWORD DB TABLE N
 * </pre>
 * where:
 * - DSN - database connection URL.
 * - USER - user name.
 * - PASSWORD - password.
 * - DB - database name.
 * - TABLE - table name.
 * - N - number of canons and symbols per canon.
 * Example:
 * <pre>
 * $ php create_mysql_data.php 'mysql:host=127.0.0.1;port=3306;charset=utf8' testuser testpass testdb table1 100
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

$dsn = $argv[1];
$user = $argv[2];
$password = $argv[3];
$db = $argv[4];
$table = $argv[5];
$count = intval($argv[6]);

$pdo = new \PDO($dsn, $user, $password);
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$sql = 'USE ' . $db . ';';
$pdo->exec($sql);
$sql = 'CREATE TABLE IF NOT EXISTS ' . $table .
       ' (canon VARCHAR(256), symbol VARCHAR(256), PRIMARY KEY (symbol), INDEX(canon))' .
       ' ENGINE = MYISAM;';
$pdo->exec($sql);
$sql = 'TRUNCATE ' . $table . ';';
$pdo->exec($sql);

mt_srand($count * $count);
for ($i=0; $i<$count; $i++)
{
    $canon = "http." . md5(mt_rand());
    $sql = "INSERT INTO " . $table . " VALUES('" .
        $canon . "', '" . $canon . "');\n";
    $pdo->exec($sql);
    for ($j=0; $j<$count; $j++)
    {
        $symbol = "http." . md5(mt_rand());
        $sql = "INSERT INTO " . $table . " VALUES('" .
            $canon . "', '" . $symbol . "');\n";
        $pdo->exec($sql);
    }
}
?>
