<?php
/**
 * SameAs Lite
 *
 * This class provides a simple timer for timing use of
 * \SameAsLite\Store->querySymbol with MySQL.
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
 * Simple timer for timing use of \SameAsLite\Store->querySymbol with
 * MySQL.
 */
class MySqlQuerySymbolTimer
{
    /** @var \PDO $dbHandle PDO object for the database, once opened */
    private $pdo;
    /** @var string $dsn PDO database connection string */
    private $dsn = null;
    /** @var string $table Store table name */
    private $table = null;
    /** @var string $user Database usename */
    private $user = null;
    /** @var string $pass Database password */
    private $pass = null;
    /** @var string $db Database name */
    private $db = null;
    /** @var string|null $dataFile Data file to append query results to */
    private $dataFile = null;

    /**
     * Constructor. Saves database connection information.
     *
     * @param string $dsn   PDO database connection string
     * @param string $table Store table name
     * @param string $user  Database username
     * @param string $pass  Database password
     * @param string $db    Database name
     */
    public function __construct($dsn, $table, $user, $pass, $db)
    {
        $this->dsn = $dsn;
        $this->table = $table;
        $this->user = $user;
        $this->pass = $pass;
        $this->db = $db;
    }

    /**
     *
     * @param string|null $dataFile Data file to append querySymbol
     * results to.
     */
    public function setDataFile($dataFile = null)
    {
        $this->dataFile = $dataFile;
    }

    /**
     * Calculate time to create \SameAsLite\Store
     * object and invoke \SameAsLite\Store->querySymbol.
     * If a data file is set then the query results are appended
     * to this file.
     *
     * @param  string   $symbol   Symbol to search for.
     * @param  int|null $expected Expected number of matching symbols.
     * @return float    Execution time in seconds.
     * @throws \Exception If the number of matching symbols does not
     * equal the expected number (if expected is > -1).
     */
    public function querySymbol($symbol, $expected = -1)
    {
        $start = microtime(true);
        $store = new \SameAsLite\Store($this->dsn,
            $this->table, $this->user, $this->pass, $this->db);
        $result = $store->querySymbol($symbol);
        $end = microtime(true);
        $total = $end - $start;
        $actual = count($result);
        if (($expected > -1) && ($actual != $expected))
        {
            throw new \Exception(
                'Unexpected number of rows. Expected: ' . $expected
                . '. Found: ' . $actual . PHP_EOL . print_r($result, TRUE));
        }
        if ($this->dataFile != null)
        {
            foreach ($result as $symbol)
            {
                file_put_contents($this->dataFile, $symbol . PHP_EOL,
                    FILE_APPEND);
            }
        }
	return $total;
    }
}
?>
