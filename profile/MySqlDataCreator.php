<?php
/**
 * SameAs Lite
 *
 * This class creates a MySQL table populated with pseudo-random data.
 *
 * @package   SameAsLite
 * @author    The Software Sustainability Institute <info@software.ac.uk>
 * @copyright 2015 The University of Edinburgh
 * @license   Apache 2.0
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

namespace SameAsLite;

/**
 * Create a MySQL table populated with pseudo-random data.
 */
class MySqlDataCreator
{
    /**
     * Create a MySQL table populated with pseudo-random data.
     * Populate a database table with randomly created canons and
     * symbols. count canons are created. Each canon has count symbols
     * created, so each canon has count + 1 pairs in the table (the +
     * 1 from the canon-canon pair).
     * A seed (count * count) is used so the random values
     * created each time are the same. If the table does not exist it
     * is created, if it does exist it is first emptied.
     *
     * @param string $dsn   PDO database connection string
     * @param string $table Store table name
     * @param string $user  Database username
     * @param string $pass  Database password
     * @param string $db    Database name
     * @param int    $count Number of canons, and canons per symbol
     *                      to create.
     */
    public static function create($dsn, $table, $user, $pass, $db, $count)
    {
        $pdo = new \PDO($dsn, $user, $pass);
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
            $canon = self::getValue(mt_rand());
            $sql = "INSERT INTO " . $table . " VALUES('" .
                $canon . "', '" . $canon . "');\n";
            $pdo->exec($sql);
            for ($j=0; $j<$count; $j++)
            {
                $symbol = self::getValue(mt_rand());
                $sql = "INSERT INTO " . $table . " VALUES('" .
                    $canon . "', '" . $symbol . "');\n";
                $pdo->exec($sql);
            }
        }
    }

    /**
     * Return a canon-symbol pair. The c-th canon is returned, where
     * canon indices range from 0..$count - 1. The s-th symbol is
     * returned, where symbol indices range from 1..$count. If s is
     * 0 then the symbol is set equal to the canon. This function
     * returns the same canon-symbol pairs as created by create.
     * @param int $count Number of canons, and canons per symbol.
     * @param int $c     Canon index.
     * @param int $s     Symbol index (0 for the canon).
     * @return array     canon-symbol or canon-canon pair.
     */
    public static function getPair($count, $c, $s = 0)
    {
        mt_srand($count * $count);
        for ($i=0; $i<$c; $i++)
        {
            mt_rand();
            for ($j=0; $j<$count; $j++)
            {
                mt_rand();
            }
        }
        $canon = self::getValue(mt_rand());
        if ($s > 0)
        {
            for ($j=1; $j<$s; $j++)
            {
                mt_rand();
            }
            $symbol = self::getValue(mt_rand());
        }
        else
        {
            $symbol = $canon;
        }
        return array($canon, $symbol);
    }

    /**
     * Given a random value return a string formed from the
     * MD5 checksum of the value prepended with "http.".
     * @param float   $randomValue Random value.
     * @return string String.
     */
    public static function getValue($randomValue)
    {
        return "http." . md5($randomValue);
    }
}
?>
