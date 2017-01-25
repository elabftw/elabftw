<?php
/**
 * \Elabftw\Elabftw\Db
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \PDO;
use \Exception;

/**
 * Connect to the database with a singleton class
 */
final class Db
{
    /** our connection */
    private $connection = null;

    /** store the single instance of the class */
    private static $instance = null;

    /** total number of queries */
    private $nq = 0;

    /**
     * Construct of a singleton is private
     *
     * @throws Exception If it cannot connect to the database
     */
    private function __construct()
    {
        try {
            $pdo_options = array();
            $pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
            $pdo_options[PDO::ATTR_PERSISTENT] = true;
            $pdo_options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
            $this->connection = new \PDO(
                'mysql:host=' . DB_HOST . ';dbname=' .
                DB_NAME,
                DB_USER,
                DB_PASSWORD,
                $pdo_options
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Return the instance of the class
     *
     * @return object $instance The instance of the class
     */
    public static function getConnection()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Db();
        }

        return self::$instance;
    }

    /**
     * Prepare a query
     *
     * @param string $sql The SQL query
     * @return \PDOStatement
     */
    public function prepare($sql)
    {
        $this->nq++;
        return $this->connection->prepare($sql);
    }

    /**
     * Make a simple query
     *
     * @param string $sql The SQL query
     * @return \PDOStatement
     */
    public function q($sql)
    {
        return $this->connection->query($sql);
    }

    /**
     * Return the last id inserted
     *
     * @return string
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Get number of SQL queries for the page
     *
     * @return int
     */
    public function getNumberOfQueries()
    {
        return $this->nq;
    }

    /**
     * Disallow cloning the class
     */
    private function __clone()
    {
        return false;
    }
    /**
     * Disallow wakeup also
     */
    private function __wakeup()
    {
        return false;
    }
}
