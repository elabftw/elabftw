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

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Config;
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
        $pdoOptions = array();
        // throw exception if error
        $pdoOptions[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        // use persistent mode for connection to MySQL
        $pdoOptions[PDO::ATTR_PERSISTENT] = true;
        // only return a named array
        $pdoOptions[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        if (!empty(Config::fromEnv('DB_CERT_PATH'))) {
            /** @psalm-suppress UndefinedConstant */
            $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = Config::fromEnv('DB_CERT_PATH');
        }

        $this->connection = new PDO(
            'mysql:host=' . Config::fromEnv('DB_HOST') . ';port=' . Config::fromEnv('DB_PORT') . ';dbname=' .
            Config::fromEnv('DB_NAME'),
            Config::fromEnv('DB_USER'),
            Config::fromEnv('DB_PASSWORD'),
            $pdoOptions
        );
    }

    /**
     * Disallow cloning the class
     */
    private function __clone() {}

    /**
     * Disallow wakeup also
     */
    public function __wakeup() {}

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
        $res = $req->fetch();
        if ($res === false || $res === null || $req->rowCount() === 0) {
            throw new ResourceNotFoundException();
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
