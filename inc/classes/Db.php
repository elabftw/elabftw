<?php
/**
 * \Elabftw\Elabftw\Db
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
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
    private static $connection = null;
    /** store the single instance of the class */
    private static $instance = null;

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
            $this->connection = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD, $pdo_options);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Return the instance of the class
     *
     * @return object $instance The instance of the class
     */
    public function getConnection()
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
     * Test is a field is present in a table
     *
     * @param string $table
     * @param string $field
     * @return bool True if field is here already
     */
    public function fieldIsHere($table, $field)
    {
        $here = false;
        $sql = "SHOW COLUMNS FROM " . $table;
        $req = $this->connection->prepare($sql);
        $req->execute();
        while ($show = $req->fetch()) {
            if (in_array($field, $show)) {
                $here = true;
            }
        }
        return $here;
    }

    /**
     * Add a field to a table
     *
     * @param string $table
     * @param string $field
     * @param string $params
     * @return bool
     */
    public function addField($table, $field, $params)
    {
        if (!$this->fieldIsHere($table, $field)) {
            $sql = "ALTER TABLE $table ADD $field $params";
            $req = $this->connection->prepare($sql);
            return $req->execute();
        }
        return false;
    }

    /**
     * Remove a field from a table
     *
     * @param string $table
     * @param string $field
     * @return bool
     */
    public function rmField($table, $field)
    {
        if ($this->fieldIsHere($table, $field)) {
            $sql = "ALTER TABLE $table DROP $field";
            $req = $this->connection->prepare($sql);
            return $req->execute();
        }
        return false;
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
