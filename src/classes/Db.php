<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use const DB_CERT_PATH;
use const DB_HOST;
use const DB_NAME;
use const DB_PASSWORD;
use const DB_PORT;
use const DB_USER;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ResourceNotFoundException;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Connect to the database with a singleton class
 */
final class Db
{
    private PDO $connection;

    // store the single instance of the class
    private static ?Db $instance = null;

    // total number of queries
    private int $nq = 0;

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
        // @phpstan-ignore-next-line
        if (defined('DB_CERT_PATH') && !empty(DB_CERT_PATH)) {
            $pdo_options[PDO::MYSQL_ATTR_SSL_CA] = DB_CERT_PATH;
        }

        // be backward compatible
        if (!defined('DB_PORT')) {
            define('DB_PORT', '3306');
        }

        $this->connection = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' .
            DB_NAME,
            DB_USER,
            DB_PASSWORD,
            $pdo_options
        );
    }

    /**
     * Disallow cloning the class
     * @norector \Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector
     */
    private function __clone()
    {
    }

    /**
     * Disallow wakeup also
     * @norector \Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector
     */
    public function __wakeup()
    {
    }

    /**
     * Return the instance of the class
     *
     * @throws PDOException If connection to database failed
     * @return Db The instance of the class
     */
    public static function getConnection(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Prepare a query
     *
     * @param string $sql The SQL query
     */
    public function prepare(string $sql): PDOStatement
    {
        $this->nq++;
        return $this->connection->prepare($sql);
    }

    /**
     * Execute a prepared statement and throw exception if it doesn't return true
     */
    public function execute(PDOStatement $req): bool
    {
        try {
            $res = $req->execute();
        } catch (PDOException $e) {
            throw new DatabaseErrorException($e);
        }
        if (!$res) {
            throw new DatabaseErrorException();
        }
        return $res;
    }

    /**
     * Force fetch() to return an array or throw exception if result is false
     * because this is hard to test
     */
    public function fetch(PDOStatement $req): array
    {
        if ($req->rowCount() === 0) {
            throw new ResourceNotFoundException();
        }
        $res = $req->fetch();
        if ($res === false || $res === null) {
            return array();
        }
        return $res;
    }

    /**
     * Make a simple query
     *
     * @param string $sql The SQL query
     */
    public function q(string $sql): PDOStatement
    {
        $res = $this->connection->query($sql);
        if ($res === false) {
            throw new DatabaseErrorException();
        }

        return $res;
    }

    /**
     * Return the last id inserted
     */
    public function lastInsertId(): int
    {
        return (int) $this->connection->lastInsertId();
    }

    /**
     * Get number of SQL queries for the page
     */
    public function getNumberOfQueries(): int
    {
        return $this->nq;
    }

    public function getAttribute(int $attr): ?string
    {
        return $this->connection->getAttribute($attr);
    }
}
