<?php
/**
 * SameAs Lite
 *
 * Factory class for \SameAsLite\Store sub-classes.
 *
 * @package   SameAsLite
 * @author    Seme4 Ltd <sameAs@seme4.com>
 * @copyright 2009 - 2015 Seme4 Ltd
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
 * Factory class for \SameAs\Store sub-classes.
 */
class StoreFactory 
{
    /**
     * Create a \SameAs\Store object using the given DSN to determine 
     * which Store sub-class to instantiate.
     *
     * @param string $dsn    PDO database connection string
     * @param string $store  Name of store (used to define database tables)
     * @param string $user   Optional database username
     * @param string $pass   Optional database password
     * @param string $db     Optional database name
     *
     * @throws \InvalidArgumentException If any parameters are deemed invalid
     */
    public static function create($dsn, $store, 
        $user = null, $pass = null, $db = null)
    {
        if (($p = strpos($dsn, ':')) !== false) 
        {
            $dbType = substr($dsn, 0, $p);
        } 
        else 
        {
            throw new \InvalidArgumentException('Invalid PDO database connection string.');
        }
        $acceptable = array('mysql', 'sqlite');
        if (!in_array($dbType, $acceptable)) 
        {
            throw new \InvalidArgumentException(
                'Invalid PDO database connection string, only "mysql" and "sqlite" databases are supported.'
            );
        }
        if ($dbType == 'mysql') 
        {
            return new \SameAsLite\MySqlStore($dsn, $store, $user, $pass, $db);
        }
        else
        {
            return new \SameAsLite\SqLiteStore($dsn, $store);
        }
    }
}

// vim: set filetype=php expandtab tabstop=4 shiftwidth=4:
