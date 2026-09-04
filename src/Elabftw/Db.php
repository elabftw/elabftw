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
use PDO;
use PDOException;
use PDOStatement;

use function debug_print_backtrace;
use function hash;
use function hrtime;
use function json_encode;
use function preg_replace;
use function round;
use function trim;

/**
 * Connect to the database with a singleton class
 */
final class Db
{
    public const int DUPLICATE_CONSTRAINT_ERROR = 1062;

    private bool $profileQueries;

    private int $queryLogMinMs;

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
        $this->profileQueries = Env::asBool('DB_QUERY_PROFILING');
        $this->queryLogMinMs = Env::asInt('DB_QUERY_LOG_MIN_MS');

        $pdoOptions = array();
        // throw exception if error
        $pdoOptions[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        // use persistent mode for connection to MySQL
        $pdoOptions[PDO::ATTR_PERSISTENT] = Env::asBool('USE_PERSISTENT_MYSQL_CONN');
        // only return a named array
        $pdoOptions[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        if (!empty(Env::asString('DB_CERT_PATH'))) {
            /** @psalm-suppress UndefinedConstant */
            $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = Env::asString('DB_CERT_PATH');
        }

        $this->connection = new PDO(
            'mysql:host=' . Env::asString('DB_HOST') . ';port=' . Env::asString('DB_PORT') . ';dbname=' .
            Env::asString('DB_NAME'),
            Env::asString('DB_USER'),
            Env::asString('DB_PASSWORD'),
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
        $startedAt = hrtime(true);
        try {
            $res = $req->execute();
        } catch (PDOException $e) {
            if (Env::asBool('DEV_MODE')) {
                debug_print_backtrace();
            }
            throw new DatabaseErrorException($e->errorInfo ?? array('OOPS', 42, 'where error?'));
        } finally {
            $this->logQuery($req->queryString, $startedAt);
        }
        if (!$res) {
            throw new DatabaseErrorException(array('OOPS', 42, 'Something went wrong :/'));
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
        $startedAt = hrtime(true);
        try {
            $res = $this->connection->query($sql);
        } finally {
            $this->logQuery($sql, $startedAt);
        }
        if ($res === false) {
            throw new DatabaseErrorException(array('OOPS', 42, 'Something went wrong :/'));
        }

        return $res;
    }

    // little wrapper to return rowCount()
    public function qToRowCount(string $sql): int
    {
        return $this->q($sql)->rowCount();
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

    public function bindNullableInt(PDOStatement $req, string $param, ?int $value): void
    {
        $req->bindValue($param, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    private function logQuery(string $sql, int $startedAt): void
    {
        if (!$this->profileQueries) {
            return;
        }

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        if ($durationMs < $this->queryLogMinMs) {
            return;
        }

        $normalizedSql = preg_replace('/\\s+/', ' ', trim($sql)) ?? $sql;
        $entry = json_encode(array(
            'event' => 'sql_query',
            'request_id' => $_SERVER['ELABFTW_REQUEST_ID'] ?? '',
            'duration_ms' => round($durationMs, 3),
            'query_id' => hash('sha256', $normalizedSql),
            // Do not log bound values: they can contain secrets or personal data.
            'sql' => $normalizedSql,
        ));

        if ($entry !== false) {
            $logger = App::getDefaultLogger();
            $logger->debug($entry);
        }
    }
}
