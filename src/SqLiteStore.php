<?php
/**
 * SameAs Lite
 *
 * This class provides a specialised storage capability for SameAs pairs.
 *
 * @package   SameAsLite
 * @author    Seme4 Ltd <sameAs@seme4.com>
 * @copyright 2009 - 2014 Seme4 Ltd
 * @link      http://www.seme4.com
 * @version   0.0.1
 * @license   MIT Public License
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace SameAsLite;

/**
 * Provides SQLite storage and management of SameAs relationships.
 * TODO Note that the is the possibility/probability of there being singleton bundles.
 *      If there are such bundles, then the system will largely ignore them.
 * TODO Some of the queries don't need Injectio Protection, as (for example canons) came out of the DB
 */
class SqLiteStore extends \SameAsLite\Store
{
    /**
     * This is the constructor for a SameAs Lite store, validates and saves
     * settings. Once a Store object is created, call the connect() function to
     * establish connection to the underlying database.
     *
     * @param string $dsn    The PDO database connection string
     * @param string $name   Name of this store (used to define database tables)
     * @param string $user   Optional database username
     * @param string $pass   Optional database password
     * @param string $dbName Optional database name
     *
     * @throws \InvalidArgumentException If any parameters are deemed invalid
     */
    public function __construct($dsn, $name)
    {
        parent::__construct($dsn, $name);
    }

    /**
     * Get DSN prefix accepted by this class.
     * @return 'sqlite'.
     */
    public function getDsnPrefix()
    {
        return 'sqlite';
    }

    /**
     * Select database to use.
     * This is a no-op for SQLite.
     * @throws \Exception Exception is thrown if connection fails or database cannot be accessed.
     */
    protected function useDatabase()
    {
    }

    /**
     * Gets SQL query to create a new store.
     * @return string An SQL statement to create a new store table.
     */
    protected function getCreateTablesSql() 
    {
        return 'CREATE TABLE IF NOT EXISTS ' . $this->storeName .
               ' (canon TEXT, symbol TEXT PRIMARY KEY)' .
               ' WITHOUT ROWID;' .
               ' CREATE INDEX IF NOT EXISTS ' . $this->storeName . '_idx' .
               ' ON ' . $this->storeName . ' (canon);';
    }

    /**
     * Gets SQL statement to delete a whole store.
     * @return string An SQL statement to delete the store table.
     */
    protected function getDeleteStoreSql()
    {
        return 'DROP TABLE IF EXISTS ' . $this->storeName . ';' .
               'DROP INDEX IF EXISTS ' . $this->storeName . '_idx;';
    }

    /**
     * Gets SQL statement to clear out a whole store, leaving an empty table.
     * @return string An SQL statement to empty the store table.
     */
    protected function getEmptyStoreSql()
    {
        return 'DELETE FROM ' . $this->storeName . ';';
    }

    /**
     * Gets SQL statement to list the sameAs stores in the database.
     * @return string An SQL statement to list all the tables.
     */
    protected function getListStoresSql()
    {
        return "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;";
    }

    /**
     * Export the contents of a store table to file. 
     * Mainly for diagnostics. but can also be used (with care) for 
     * backup/restore.
     * @param string $file The file name to which the data is written (optional).
     * @throws \Exception always as this is unsupported for SQLite.
     */
    public function exportToFile($file = null)
    {
        throw new \Exception('This function is not supported for SQLite databases');
    }

    /**
     * Takes the output of exportToFile and loads into a store table.
     * Overwrites any existing values, leaving the others intact.
     * Assumes the source data is valid.
     * @param string $file The file name of the source data to be asserted.
     * @throws \Exception always as this is unsupported for SQLite.
     */
    public function loadFromFile($file)
    {
        throw new \Exception('This function is not supported for SQLite databases');
    }
}

// vim: set filetype=php expandtab tabstop=4 shiftwidth=4:
