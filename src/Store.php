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

ini_set('max_execution_time', 1800);

/**
 * Provides storage and management of SameAs relationships.
 */
class Store
{
    const DBNAMEDEFAULT = "sameaslitedb";
    const CANON = 1;
    const NOTCANON = 0;

    // PDO needs different treatment for different DBs
    // sqlite|mysql currently supported
    //private $dbType = 'mysql'; // TODO mySql is not tested or complete, but does work
    private $dbType = 'sqlite';

    /**
     * @var string $dbName The DB name - for SQLite, this is the path and
     * name of the file to keep the database in for SQLite. Default defined in
     * the class file. The sqlite extension is added by the code.
     */
    protected $dbName;

    /** @var \PDO $dbHandle The PDO object for the DB, once opened */
    protected $dbHandle;

    /** @var string $store The name of the store */
    protected $storeName;

    /**
     * This is the constructor for a sameAsLite store
     *
     * Create a sameAs service
     *    And the table to put it in:
     *        symbol - the symbol - hopefully a URI
     *        Bundle - the bundle identifier for the symbol
     *        Flags - Information, such as Canon - at the moment
     *                Canon=self::CANON, self::NOTCANON otherwise
     *
     * TODO: remove dependencies on SQLite. DB should be configured via a PDO
     * connection string, and/or via helper methods eg connectMySQL(...) or
     * connectSQLite(...)
     *
     * @param string $storeName The string for the name of the store
     * @param string $dbName    The DB name
     *
     * @throws \InvalidArgumentException If the database cannot be accessed, an
     * Exception is thrown.
     */
    public function __construct($storeName, $dbName = null)
    {
        // TODO? Ensure ownership, group and perms are right on the DB file, if using SQLite
        // validate parameters
        if (!is_string($storeName)) {
            throw new \InvalidArgumentException('$storeName parameter is expected to be a string');
        }
        if ($dbName != null && !is_string($storeName)) {
            throw new \InvalidArgumentException('$dbName parameter is expected to be a string');
        }

        // Determine the database name
        $this->dbName = (($dbName == null) ? self::DBNAMEDEFAULT : $dbName);

        // Create the DB object and remember it
        try {
            switch ($this->dbType) {
                case 'sqlite':
                    $this->dbHandle = new \PDO("sqlite:$this->dbName".".sqlite");
                    break;
                case 'mysql':
                    // TODO It seems to need to have the DB already created before I can do anything :-(
                    // TODO This is probably the sam eproblem that causes the DB list to fail
                    $this->dbHandle =
                        new \PDO("mysql:host=localhost;port=3306;dbname=$this->dbName;charset=utf8", "root", "mysql");
                    break;
                default: $this->error("Unknown DB type '$this->dbType' requested");
            }
        } catch (\PDOException $e) {
            throw new \InvalidArgumentException(
                'Unable to open database with name "' . $this->dbName . '" ' .
                $e->getMessage()
            );
        }

        // Remember the store name
        $this->storeName = $storeName;

        // For debugging and sanity, make PDO report any problems, not fail silently
        $this->dbHandle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // One table required, with the same name as the store
        try {
            $sql = 'CREATE TABLE IF NOT EXISTS ' . $this->storeName .
                   ' (Symbol varchar(256) PRIMARY KEY, Bundle integer, Flags integer)';
            $this->dbHandle->exec($sql);
        } catch (\PDOException $e) {
            throw new \InvalidArgumentException(
                'Failed to create Store with name ' . $this->storeName . " query: =$sql=" .
                $e->getMessage()
            );
        }
    }

    /**
     * This is the destructor for a sameAsLite store, destroys the PDO object
     *
     * @throws \PDOException An exception could be thrown if we fail to destroy
     * the database.
     */
    public function __destruct()
    {
        $this->dbHandle = null;
    }

    /**
     * This is the simple method to query a store with a symbol
     *
     * Looks up the given symbol in a store, and returns the bundle with all
     * the symbols in it (including the one given). The bundle is ordered with
     * canon(s) first, then non-canons, in alpha order of symbols.
     *
     * @param string $symbol The symbol to be looked up
     *
     * @return string[] An array of the symbols, which is singleton of the given symbol of nothing was found
     */
    public function querySymbol($symbol)
    {
        try {
            // Do we have it?
            $b = $this->queryGetBundleID($symbol);
            if ($b === null) {
                // No we don't have it already
                $output = array($symbol);
            } else {
                // Yes we do have it already
                $statement = $this->dbHandle->prepare(
                    "SELECT Symbol, Flags FROM $this->storeName WHERE Bundle = '$b' ORDER BY Flags DESC, Symbol ASC;"
                );
                $statement->execute();
                $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
                $output = array();
                foreach ($result as $row) {
                    $output[] = $row['Symbol'];
                }
            }
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Query symbol '$s' failed");
        }

        return $output;
    }

    /**
     * Search for symbols in this store that contain the given pattern
     *
     * Looks up the given symbol in a store, and returns the bundle with all
     * the symbols in it (including the one given).
     * The bundle is ordered with canon(s) first, then non-canons, in alpha
     * order of symbols.
     *
     * @param string $string The string to be looked up
     *
     * @return string[] An array of the symbols, which is enpty if none were found.
     */
    public function search($string)
    {
        try {
            // Do we have it at all?
            $statement = $this->dbHandle->prepare(
                "SELECT Symbol FROM $this->storeName WHERE Symbol LIKE :string ORDER BY Symbol;"
            );
            $statement->bindValue(':string', "%$string%", \PDO::PARAM_STR);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_NUM);
            $output = array();
            foreach ($result as $row) {
                $output[] = $row[0];
            }
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Search for '$string' failed");
        }

        return $output;
    }

    /**
     * Put a possibly new pair into the store
     *
     * Needs to cope with a number of situations:
     *        Both symbols were already in the store
     *          - do nothing
     *        The first symbol was not in the store
     *          - add it to the bundle of the second symbol
     *        The second symbol was not in the store
     *          - add it to the bundle of the first symbol
     *        Both symbols were in different bundles
     *          - add the symbols from the bundle of the second symbol to the
     *            first bundle, none of them as canons
     *            (leaving the canons situation as it is in bundle 1)
     *
     * @param string $symbol1 The first symbol
     * @param string $symbol2 The second symbol
     */
    public function assertPair($symbol1, $symbol2)
    {
        try {
            // Are the symbols already in the store?
            $bundleID1 = $this->queryGetBundleID($symbol1);
            $bundleID2 = $this->queryGetBundleID($symbol2);
            // $bundleID1 & $bundleID2 now have the bundleIDs, or null if there wasn't one

            if ($bundleID1 === null && $bundleID2 === null) {
                // Both symbols are new - create a new bundle
                // First we find the maximum Bundle identifer, so we can use the next one up
                // And make the Canon the $s1
                $bundle = 1 + $this->queryGetMaxBundle();
                $this->queryAssertRow($symbol1, $bundle, self::CANON);
                $this->queryAssertRow($symbol2, $bundle, self::NOTCANON);
            } elseif ($bundleID1 === null) {
                // Insert new $symbol1 into existing $bundleID2
                // No need to do anything about canons
                $this->queryAssertRow($symbol1, $bundleID2, self::NOTCANON);
            } elseif ($bundleID2 === null) {
                // Insert new $symbol2 into existing $bundleID1
                // No need to do anything about canons
                $this->queryAssertRow($symbol2, $bundleID1, self::NOTCANON);
            } elseif ($bundleID1 === $bundleID2) {
                // They were both already in the same bundle
                // Do nothing
            } else {
                // They are in different bundles
                // So join the two bundles - set all of bundle 2 to be in bundle 1
                // Canon will be the canon of bundle 1, since the changed ones all get Flags=self::NOTCANON
                $symbols = $this->queryGetBundleSymbols($bundleID2);
                foreach ($symbols as $symbol) {
                    $this->queryAssertRow($symbol, $bundleID1, self::NOTCANON);
                }
            }
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Unable to assert pair ($symbol1, $symbol2)");
        }
    }

    /**
     * Take an array of strings of lines of Tab-separated symbols and assert into the store
     *
     * In fact, takes just the first two TAB fields - you can have anything you
     * like after a second TAB, if there is one.
     * Does no checking - simply skips lines that don't have a TAB in them.
     *
     * @param string[] $data The array of pairs
     */
    public function assertPairs($data)
    {
        foreach ($data as $line) {
            $line = trim($line);
            $bits = explode("\t", $line);
            if (count($bits) >= 2) {
                $this->assertPair($bits[0], $bits[1]);
            }
        }
    }

    /**
     * Take a file of Tab-separated symbols and assert into the store
     *
     * Not a public service - just in the Class
     * In fact, takes just the first two TAB fields - you can have anything you
     * like after a second TAB, if there is one.
     * Does no checking - simply skips lines that don't have a TAB in them.
     *
     * @param string $file The filename to be asserted
     */
    public function assertFile($file)
    {
        $data = file($file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
        if ($data === false) {
            $this->error("Failed to open file '$file'");
        }
        $this->assertPairs(explode("\n", $data));
    }

    /**
     * Simply remove a symbol from this store
     *
     *    Note - if it is the canon, then there will be no canon left
     *        It could choose another one, but has no way of knowing - so you are advised to setCanon after this.
     *
     * @param string $symbol The symbol to be deleted.
     */
    public function removeSymbol($symbol)
    {
        $this->queryDeleteSymbol($symbol);
    }

    /**
     * Make the given symbol the (only) Canon of it's bundle
     *
     *    Any existing canons will be uncanonised.
     *    If it isn't there, then it becomes a singleton bundle
     *        Note - this may be slightly unexpected, but is the only sensible thing to do
     *
     * @param string $symbol The symbol to be set as the canon
     */
    public function setCanon($symbol)
    {
        // Do we have it?
        $bundleID = $this->queryGetBundleID($symbol);
        if ($bundleID === null) {
            // No we don't have it already
            // Insert new $symbol into new $bundleID
            // And make it the Canon of its singleton bundle
            $this->queryAssertRow($symbol, $this->queryGetMaxBundle() + 1, self::CANON);
        } else {
            // Yes we do have it already
            // Get the Canon and de-canonise it
            $oldCanon = $this->queryGetCanon($bundleID);
            $this->queryAssertRow($oldCanon, $bundleID, self::NOTCANON);
            // And make $symbol the Canon
            $this->queryAssertRow($symbol, $bundleID, self::CANON);
        }
    }

    /**
     * Return the Canon of the bundle with the given symbol in it
     *
     * Looks up the given symbol in a store, and returns the canon of bundle it is in.
     * If the symbol was not in the store at all, then it simply returns the symbol itself.
     *
     * @param string $symbol The symbol that we want the canon of
     *
     * @return string[] A singleton array with the canon in it
     */
    public function getCanon($symbol)
    {
        // Do we have it?
        $bundle = $this->queryGetBundleID($symbol);
        if ($bundle === null) {
            // No we don't have it already
            $canon = "$symbol";
        } else {
            // Yes we do have it already
            $canon = $this->queryGetCanon($bundle);
        }

        return array($canon);
    }

    /**
     * Return all the Canons in a store
     *
     * @return string[] An array with all the canons in it
     */
    public function allCanons()
    {
        try {
            $statement = $this->dbHandle->prepare(
                "SELECT Symbol FROM $this->storeName WHERE " .
                "Flags=" . self::CANON . " ORDER BY Symbol ASC"
            );
            $statement->execute();
            $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $output = array();
            foreach ($results as $row) {
                $output[] = $row['Symbol'];
            }
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Database failure to get canons from store");
        }

        return $output;
    }

    /**
     * Simply delete a whole store
     */
    public function deleteStore()
    {
        try {
            $statement = $this->dbHandle->prepare("DROP TABLE $this->storeName;");
            $statement->execute();
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Database failure to delete store");
        }
    }

    /**
     * Simply clear out the whole store, leaving an empty table
     */
    public function emptyStore()
    {
        try {
            if ($this->dbType === 'sqlite') {
                // SQLite doesn't have TRUNCATE
                $statement = $this->dbHandle->prepare("DELETE FROM $this->storeName;");
            } else {
                $statement = $this->dbHandle->prepare("TRUNCATE $this->storeName;");
            }
            $statement->execute();
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Database failure to empty store");
        }
    }

    /**
     * Mainly for diagnostics, but can be used to back up or move stores
     *  Output the whole sameAs table.
     *  First line is headers.
     *  Subsequent lines are each entry.
     *  Represented as an array of one string per line.
     *
     *  A dump that has been saved to file can be re-asserted using restoreStore.
     *
     * @return string[] The array of strings
     */
    public function dumpStore()
    {
        try {
            $statement = $this->dbHandle->prepare(
                "SELECT * FROM $this->storeName " .
                "ORDER BY Bundle ASC, Flags DESC, Symbol ASC"
            );
            $statement->execute();
            $results = $statement->fetchAll(\PDO::FETCH_ASSOC);

            $output = array();
            $output[] = "Bundle\tFlags\tSymbol";
            foreach ($results as $row) {
                $output[] = "{$row['Bundle']}\t{$row['Flags']}\t{$row['Symbol']}";
            }
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Unable to dump store");
        }
        return $output;
    }

    /**
     * Takes the output of dumpStore and adds it into this store
     *
     * Overwrites any existing values, leaving the others intact.
     * Assumes the source data is valid.
     *
     * @param string $file The file name of the source data to be asserted
     */
    public function restoreStore($file)
    {
        try {
            $data = file($file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
            if ($data === false) {
                $this->error("Failed to open file '$file'");
            }
            array_shift($data); // Lose the heading line
            foreach ($data as $line) {
                $line = trim($line);
                $bits = explode("\t", $line);
                $this->queryAssertRow($bits[2], $bits[0], $bits[1]);
            }
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Unable to dump store");
        }
    }

    /**
     * List the sameAs stores in this database
     *
     * Effectively just lists all the tables - it could try to check to see if
     * they seem to be sameAs or not, but in fact that would always be
     * problematic at the limit.
     * In fact will simply throw an SQL error if there is no Symbol column.
     *
     * @return string[] A header line followed by statistics on each store
     */
    public function listStores()
    {
        try {
            if ($this->dbType === 'sqlite') {
                // SQLite doesn't have SHOW TABLES"
                $statement = $this->dbHandle->prepare(
                    "SELECT name FROM sqlite_master " .
                    "WHERE type='table' ORDER BY name;"
                );
            } else {
                $statement = $this->dbHandle->prepare("SHOW TABLES");
            }
            $statement->execute();
            $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

            $output = array();
            $output[] = "Symbols\tBundles\tStore";
            foreach ($rows as $row) {
                $store = $row['name'];
                $statement = $this->dbHandle->prepare("SELECT COUNT(DISTINCT Symbol) FROM $store;");
                $statement->execute();
                $count = $statement->fetch(\PDO::FETCH_NUM);
                $statement = $this->dbHandle->prepare("SELECT COUNT(DISTINCT Bundle) FROM $store ;");
                $statement->execute();
                $bundles = $statement->fetch(\PDO::FETCH_NUM);
                $output[] = "{$count[0]}\t{$bundles[0]}\t$store";
            }
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Database failure to get the store list");
        }
        return $output;
    }

    /**
     * Provide basic statistics on the store - number of symbols and number of bundles
     *
     * @return string[] The array wih the results in
     */
    public function statistics()
    {
        $output = array();
        $output[] = "Statistics for sameAs store $this->storeName:";
        try {
            //  get number of symbols
            $statement = $this->dbHandle->prepare(
                "SELECT COUNT(DISTINCT Symbol) FROM $this->storeName ;"
            );
            $statement->execute();
            $symbols = $statement->fetch(\PDO::FETCH_NUM);
            $output[] = $symbols[0]."\tsymbols";

            //  get number of bundles
            $statement = $this->dbHandle->prepare(
                "SELECT COUNT(DISTINCT Bundle) FROM $this->storeName ;"
            );
            $statement->execute();
            $bundles = $statement->fetch(\PDO::FETCH_NUM);
            $output[] = $bundles[0]."\tbundles";
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Database failure to get statistics for store");
        }
        return $output;
    }

    /**
     * Provide detailed analysis of the store
     *
     *    Number of symbols
     *    Number of bundles
     *    Average and median symbols per bundle
     *    Table of count of bundles for each bundle size
     *
     *    Number of http, https and non-http(s) symbols
     *
     *    URI(s) per domain - for http(s) symbols
     *
     *    List of singleton bundle symbols
     *    Bundles without a canon
     *    Bundles with more than one canon
     *
     * @return string[] The array wih the results in
     */
    public function analyse()
    {
        $output = array();
        $output[] = "Analysis of sameAs store '$this->storeName' in Database '$this->dbName':";
        // Can return from the middle of the store is actually empty
        try {
            // Just get the whole store into an array to work on
            $statement = $this->dbHandle->prepare(
                "SELECT * FROM $this->storeName ORDER BY Bundle ASC, Flags DESC, Symbol ASC;"
            );
            $statement->execute();
            $store = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if (count($store) === 0) {
                $output[] = "Store is empty!";
                return $output;
            };

            $nSymbols = 0;            // Symbols in the store
            $nBundles = 0;            // Bundles in the store
            $bundlesAssoc = array();    // An array of bundleID => array of symbols
            $bundleSizes = array();        // An array of bundleID => bundle size
            $httpSymbols = array();        // Array of symbols that have http:// at the start
            $httpsSymbols = array();    // Array of symbols that have https:// at the start
            $plainSymbols = array();    // Array of symbols that have neither http:// nor https:// at the start
            $httpDomains = array();        // Array of http domains, with symbol counts in
            $httpTLDDomains = array();    // Array of http TLD domains, with symbol counts in
            $http2LDDomains = array();    // Array of http second level+TLD domains, with symbol counts in
            $httpsDomains = array();    // Array of https domains, with symbol counts in
            $httpsTLDDomains = array();    // Array of https TLD domains, with symbol counts in
            $https2LDDomains = array();    // Array of https second level+TLD domains, with symbol counts in

            foreach ($store as $row) {
                $s = $row['Symbol'];
                $b = $row['Bundle'];
                $f = $row['Flags'];
                $nSymbols++;
                $bundlesAssoc[$b][] = $s;
                $bundleSizes[$b]++;
                //  TODO preg_match is slow, use substr if regexp is no required.
                if (preg_match('%^http://%', $s)) {
                    // http:// URI
                    $httpSymbols[] = $s;
                    // Get and record the domain name
                    preg_match('@^(?:http://)?([^/]+)@i', $s, $matchesD);
                    $httpDomains[] = $matchesD[1];
                    // Get and record the last two bits of the domain name
                    preg_match('/([^.]+)\.([^.]+)$/', $matchesD[1], $matches2D);
                    $http2LDDomains[] = $matches2D[0];
                    // Record the TLD itself
                    $httpTLDDomains[] = $matches2D[2];
                } elseif (preg_match('%^https://%', $s)) {
                    // https:// URI
                    $httpsSymbols[] = $s;
                    // Get and record the domain name
                    preg_match('@^(?:https://)?([^/]+)@i', $s, $matchesD);
                    $httpsTLDDomains[] = $matchesD[1];
                    // Get and record the last two bits of the domain name
                    preg_match('/[^.]+\.[^.]+$/', $matchesD[1], $matches2D);
                    $https2LDDomains[] = $matches2D[0];
                    // Record the TLD itself
                    $httpsTLDDomains[] = $matches2D[2];
                } else {
                    // Not an http(s) symbol
                    $plainSymbols[] = $s;
                }
            }
            $nBundles = count($bundlesAssoc);

            $sep = "=====================================================";
            $minisep = "------------------------";
            $output[] = $sep;
            // Basic numeric statistics
            $output[] = "Basic numeric statistics:";
            $output[] = "$nSymbols \tsymbols";
            $output[] = "$nBundles \tbundles";
            $output[] = sprintf("%.2f", $nSymbols/$nBundles)."\tsymbols per bundle";
            $sortedBundleSizes = $bundleSizes;
            sort($sortedBundleSizes);
            $middle = floor($nBundles / 2);
            $output[] = $bundleSizes[$middle]."\tbundle size median";
            $values = array_count_values($bundleSizes);
            $mode = array_search(max($values), $values);
            $output[] = "$mode\tbundle size mode";

            $output[] = $sep;
            // Table of count of bundles for each bundle size
            $output[] = "Table of count of bundles for each bundle size";
            $bundleSizeFrequency = array_count_values($bundleSizes);
            $output[] = "Size\tBundle Count";
            foreach ($bundleSizeFrequency as $size => $count) {
                $output[] = "$size\t$count";
            }
            $output[] = $sep;
            // Number of http, https and non-http(s) symbols
            $output[] = "Symbols by type:";
            $output[] = count($httpSymbols)."\tHTTP symbols";
            $output[] = count($httpsSymbols)."\tHTTPS symbols";
            $output[] = count($plainSymbols)."\tnon-HTTP(S) symbols";
            $output[] = $sep;
            // URI(s) etc per domain - for http symbols
            $output[] = "URI(s) etc per domain - for http symbols, if any";
            if (!empty($httpSymbols)) {
                $output[] = "Count\tDomain";
                $domainCountFrequency =  array_count_values($httpDomains);
                foreach ($domainCountFrequency as $domain => $size) {
                    $output[] = "$size\t$domain";
                }
                $output[] = $minisep;
                $output[] = "Count\tBase+TLD Domain";
                $domainCountFrequency =  array_count_values($http2LDDomains);
                foreach ($domainCountFrequency as $domain => $size) {
                    $output[] = "$size\t$domain";
                }
                $output[] = $minisep;
                $output[] = "Count\tTLD Domain";
                $domainCountFrequency =  array_count_values($httpTLDDomains);
                foreach ($domainCountFrequency as $domain => $size) {
                    $output[] = "$size\t$domain";
                }
            }
            $output[] = $sep;
            // URI(s) etc per domain - for https symbols
            $output[] = "URI(s) etc per domain - for https symbols, if any";
            if (!empty($httpsSymbols)) {
                $output[] = "Count\tDomain";
                $domainCountFrequency =  array_count_values($httpsDomains);
                foreach ($domainCountFrequency as $domain => $size) {
                    $output[] = "$size\t$domain";
                }
                $output[] = $minisep;
                $output[] = "Count\tBase+TLD Domain";
                $domainCountFrequency =  array_count_values($https2LDDomains);
                foreach ($domainCountFrequency as $domain => $size) {
                    $output[] = "$size\t$domain";
                }
                $output[] = $minisep;
                $output[] = "Count\tTLD Domain";
                $domainCountFrequency =  array_count_values($httpsTLDDomains);
                foreach ($domainCountFrequency as $domain => $size) {
                    $output[] = "$size\t$domain";
                }
            }
            $output[] = $sep;
            $output[] = "Things that might be considered errors:";
            // List of singleton bundle symbols
            $output[] = "Singleton bundles:";
            $singletons = array_keys($bundleSizes, 1);
            $output[] = "Bundle\tSymbol";
            foreach ($singletons as $singleton) {
                $output[] = "$singleton\t{$bundlesAssoc[$singleton][0]}";
            }

            $output[] = $minisep;
            $output[] = "Bundles that have canon issues:";
            // Now run through doing sanity checks on bundles
            $previousBundle = -1;        // Start with an invalid bundle number, so it won't match the first bundle
            $previousSymbol = "";        // To report soemthing useful
            $canonCount = 1;        // And pretend that the previous one had a canon
            foreach ($store as $row) {
                // $store is sorted by bundle during the original query
                $s = $row['Symbol'];
                $b = $row['Bundle'];
                $f = $row['Flags'];
                if ($b !== $previousBundle) {
                    // Then we have changed to a new bundle
                    // Report any problems with the previous one
                    if ($canonCount === 0) {
                        $output[] = "$previousBundle\thas no canon (a symbol from the bundle is '$previousSymbol')";
                    }
                    if ($canonCount > 1) {
                        $output[] = "$previousBundle\thas $canonCount canons " .
                              "(a symbol from the bundle is '$previousSymbol')";
                    }
                    // And set up for the next bundle
                    $previousBundle = $b;
                    $previousSymbol = $s;
                    $canonCount = 0;
                }
                if ($f == self::CANON) {
                    $canonCount++;
                } // Use == because $f is a string!
            }
            $output[] = $sep;
            $output[] = "(You can get all the canons by invoking the appropriate method/service)";
            $output[] = $sep;
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Database failure to get some analysis data for store");
        }

        return $output;
    }

    /**
     * Raw DB function to get the bundle ID for a given symbol
     * [sqlInjectionProtected]
     *
     * @param string $symbol The symbol being looked up
     *
     * @return int The bundle ID, null if the symbol was not in the store
     */
    private function queryGetBundleID($symbol)
    {
        try {
            $statement = $this->dbHandle->prepare("SELECT Bundle FROM $this->storeName WHERE Symbol = :symbol LIMIT 1");
            $statement->bindValue(':symbol', $symbol, \PDO::PARAM_STR);
            $statement->execute();
            $bundles = $statement->fetch(\PDO::FETCH_NUM);
            return $bundles[0];
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Database failure to get the bundleID for '$symbol'");
        }
    }

    /**
     * Raw DB function to get all the symbols from a given bundle (by ID)
     *
     *    SQL injection protected
     *
     * @param integer $bundleID The bundle being looked up
     *
     * @return string[] The bundle ID, empty if the symbol was not in the store
     */
    private function queryGetBundleSymbols($bundleID)
    {
        try {
            $statement = $this->dbHandle->prepare("SELECT Symbol FROM $this->storeName WHERE Bundle = :bundle;");
            $statement->bindValue(':bundle', $bundleID, \PDO::PARAM_INT);
            $statement->execute();
            $rs = $statement->fetchAll(\PDO::FETCH_NUM);
            $result = array();
            foreach ($rs as $r) {
                $result[] = $r[0];
            }

            return $result;
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Database failure to get the bundle symbols for bundle '$bundleID'");
        }
    }

    /**
     * Raw DB function to find the maximum value for the Bundle column in a store
     *
     *    SQL injection protected
     *
     * @return integer The maximum bundle value in the store, 0 if there are no bundles
     */
    private function queryGetMaxBundle()
    {
        try {
            $statement = $this->dbHandle->prepare("SELECT MAX(Bundle) FROM $this->storeName;");
            $statement->execute();
            $r = $statement->fetch(\PDO::FETCH_NUM);

            return $r[0];
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Database failure to get the maximum bundle ID");
        }
    }

    /**
     * Raw DB function to get the canon from a given bundle (by ID)
     *
     *    SQL injection protected
     *
     * @param integer $bundleID The bundle being looked up
     *
     * @return string The symbol which is the canon
     */
    private function queryGetCanon($bundleID)
    {
        try {
            $statement = $this->dbHandle->prepare(
                "SELECT Symbol FROM $this->storeName WHERE " .
                "Bundle = :bundle AND Flags = " . self::CANON . " LIMIT 1;"
            );
            $statement->bindValue(':bundle', $bundleID, \PDO::PARAM_INT);
            $statement->execute();
            $r = $statement->fetch(\PDO::FETCH_NUM);

            return $r[0];
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Database failure to get the get the canon for bundle '$bundleID'");
        }
    }

    /**
     * Raw DB function to delete the symbol (and whole row) from a store
     *
     *    SQL injection protected
     *
     * @param string $symbol The symbol to be deleted
     */
    private function queryDeleteSymbol($symbol)
    {
        try {
            $statement = $this->dbHandle->prepare("DELETE FROM $this->storeName WHERE Symbol = :symbol;");
            $statement->bindValue(':symbol', $symbol, \PDO::PARAM_STR);
            $statement->execute();
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Database failure to delete '$symbol'");
        }
    }

    /**
     * Raw DB function to insert or update the row of an existing symbol
     *
     * This will do an insert if it wasn't there, or update if it was.
     *
     * [sqlInjectionProtected]
     *
     * @param string  $symbol    The symbol being considered
     *
     * @param integer $bundleID  The bundle ID for the symbol is now in
     * (overwrites any existing bundle ID for an existing symbol)
     *
     * @param integer $canonFlag The Flags for this symbol (overwrites any
     * existing Flags for an existing symbol).
     */
    private function queryAssertRow($symbol, $bundleID, $canonFlag)
    {
        try {
            $statement = $this->dbHandle->prepare("REPLACE INTO $this->storeName VALUES (:symbol, :bundle, :canon)");
            $statement->bindValue(':symbol', $symbol, \PDO::PARAM_STR);
            $statement->bindValue(':bundle', $bundleID, \PDO::PARAM_INT);
            $statement->bindValue(':canon', $canonFlag, \PDO::PARAM_INT);
            $statement->execute();
        } catch (\PDOException $e) {
            print $e->getMessage();
            $this->error("Database failure to assert for '$symbol' with bundle='$bundleID' and canon='$canonFlag'");
        }
    }

    /**
     * Class error function
     *
     * Raises and exception with the given message
     *
     * @param string $message The error message to display
     *
     * @throws \Exception A generic exception is thrown, containing useful
     * details and the desired error message.
     */
    private function error($message)
    {
        throw new \Exception(get_class() . " (store '$this->storeName'): $message");
    }
}

// vim: set filetype=php expandtab tabstop=4 shiftwidth=4:
