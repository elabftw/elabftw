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
declare(strict_types=1);

namespace Elabftw\Elabftw;

use PDOException;
use PDO;

/**
 * Connect to the database with a singleton class
 */
final class Db
{
    /** @var PDO $connection Connection to PDO */
    private $connection;

    /** @var Db|null $instance store the single instance of the class */
    private static $instance;

    /** @var int $nq total number of queries */
    private $nq = 0;

    /**
     * Construct of a singleton is private
     *
     * @throws PDOException If it cannot connect to the database
     */
    private function __construct()
    {
        $pdo_options = array();
        // throw exception if error
        $pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        // use persistent mode for connection to MySQL
        $pdo_options[PDO::ATTR_PERSISTENT] = true;
        // only return a named array
        $pdo_options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;

        $this->connection = new PDO(
            'mysql:host=' . \DB_HOST . ';dbname=' .
            \DB_NAME,
            \DB_USER,
            \DB_PASSWORD,
            $pdo_options
        );
    }

    /**
     * Return the instance of the class
     *
     * @throws PDOException If connection to database failed
     * @return Db The instance of the class
     */
    public static function getConnection(): Db
    {
        if (self::$instance === null) {
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
    public function prepare($sql): \PDOStatement
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
    public function q($sql): \PDOStatement
    {
        return $this->connection->query($sql);
    }

    /**
     * Return the last id inserted
     *
     * @return int
     */
    public function lastInsertId(): int
    {
        return (int) $this->connection->lastInsertId();
    }

    /**
     * Get number of SQL queries for the page
     *
     * @return int
     */
    public function getNumberOfQueries(): int
    {
        return $this->nq;
    }

    /**
     * Disallow cloning the class
     */
    private function __clone()
    {
    }

    /**
     * Disallow wakeup also
     */
    public function __wakeup()
    {
    }
}
