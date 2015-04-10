<?php

/**
 * SameAs Lite
 *
 * This class tests the functionality of \SameAsLite\Store
 *
 * @package   SameAsLite
 * @author    Seme4 Ltd <sameAs@seme4.com>
 * @copyright 2009 - 2014 Seme4 Ltd
 * @link      http://www.seme4.com
 * @license   MIT Public License
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace SameAsLite;

/**
 * PHPUnit tests for the \SameAsLite\Store class.
 */
class StoreTest extends \PHPUnit_Extensions_Database_TestCase
{
    /** @var string DSN Connection string to use **/
    private $dsn = null;

    /** @var string|null Database name **/
    private $db_name = null;

    /** @var string|null Database username **/
    private $user = null;

    /** @var string|null Database password **/
    private $password = null;

    /** @var string Name of the store (used to form table names) **/
    private $store_name = null;

    /** @var \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection Connection object **/
    private $connection;

    /** @var \PDO PDO object for the DB, once opened **/
    private $pdo;

    /**
     * Set up test fixture.
     * Read database configuration from phpUnit $GLOBALS array.
     * The following entries are sought:
     * - DB_DSN - database DSN.
     * - DB_NAME - database name (optional for SQLite).
     * - DB_USER - username (optional for SQLite)
     * - DB_PASSWORD - password (optional for SQLite).
     */
    public function setUp()
    {
        $this->dsn = $GLOBALS['DB_DSN'];
        if (array_key_exists('DB_NAME', $GLOBALS)) {
            $this->db_name = $GLOBALS['DB_NAME'];
        }
        if (array_key_exists('DB_USER', $GLOBALS)) {
            $this->user = $GLOBALS['DB_USER'];
        }
        if (array_key_exists('DB_PASSWORD', $GLOBALS)) {
            $this->password = $GLOBALS['DB_PASSWORD'];
        }
        $this->store_name = str_replace("\\","_",get_class());
        parent::setUp();
    }

    /**
     * Tear down test fixture.
     * Drop database table that may have been created.
     */
    public function tearDown()
    {
        $this->pdo->exec("DROP TABLE IF EXISTS " . $this->store_name . ";");
        parent::tearDown();
    }

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        $this->pdo = new \PDO($this->dsn, $this->user, $this->password);
        $this->connection = $this->createDefaultDBConnection($this->pdo, $this->db_name);
        return $this->connection;
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
    }

    /**
     * Check that an exception is raised if DSN name is invalid
     *
     * @covers            \SameAsLite\Store::__construct
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionIsRaisedForInvalidDSN()
    {
        new \SameAsLite\Store('foo:baa', $this->store_name);
    }

    /**
     * Check that an exception is raised if store name is invalid
     *
     * @covers            \SameAsLite\Store::__construct
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionIsRaisedForInvalidDbaseTableName()
    {
        new \SameAsLite\Store($this->dsn, 'invalid name!');
    }

    /**
     * Check the Store can be successfully created
     *
     * @covers \SameAsLite\Store::__construct
     */
    public function testStoreCanBeConstructedForValidConstructorArguments()
    {
        $s = new Store($this->dsn, $this->store_name, $this->user, $this->password, $this->db_name);
        $this->assertInstanceOf('SameAsLite\\Store', $s);
    }

    /**
     * Check an empty Store can be successfully dumped
     *
     * @covers \SameAsLite\Store::dumpStore
     */
    public function testAnEmptyStoreCanBeDumped()
    {
        $s = new Store($this->dsn, $this->store_name, $this->user, $this->password, $this->db_name);
        $expected = array();
        $result = $s->dumpStore();
        $this->assertEquals($expected, $result);
    }

    /**
     * Check a new pair can be added and the Store state is as expected
     *
     * @covers \SameAsLite\Store::assertPair
     */
    public function testAssertPair()
    {
        $canon = "http://www.wikidata.org/entity/Q1953777";
        $symbol = "http://dbpedia.org/resource/Oxford";

        $s = new Store($this->dsn, $this->store_name, $this->user, $this->password, $this->db_name);
        $s->assertPair($canon, $symbol);

        $canons = $s->allCanons();
        $this->assertEquals(1, count($canons));
        $this->assertTrue(in_array($canon, $canons));

        $symbols = $s->querySymbol($canon);
        $this->assertEquals(2, count($symbols));
        $this->assertTrue(in_array($canon, $symbols));
        $this->assertTrue(in_array($symbol, $symbols));

        $symbols = $s->querySymbol($symbol);
        $this->assertEquals(2, count($symbols));
        $this->assertTrue(in_array($canon, $symbols));
        $this->assertTrue(in_array($symbol, $symbols));

        $canons = $s->getCanon($canon);
        $this->assertEquals(1, count($canons));
        $this->assertTrue(in_array($canon, $canons));

        $canons = $s->getCanon($symbol);
        $this->assertEquals(1, count($canon));
        $this->assertTrue(in_array($canon, $canons));
    }
}

// vim: set filetype=php expandtab tabstop=4 shiftwidth=4:
