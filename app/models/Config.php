<?php
/**
 * \Elabftw\Elabftw\Config
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;

/**
 * The general config table
 */
class Config
{
    /** db connection */
    protected $pdo;

    /**
     * get pdo
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

    /**
     * Read the configuration values
     *
     * @param string|null $confName optionnal param to get only one value
     * @return array|string
     */
    public function read($confName = null)
    {
        $final = array();

        $sql = "SELECT * FROM config";
        $req = $this->pdo->prepare($sql);
        $req->execute();
        $config = $req->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);
        if ($confName !== null) {
            return $config[$confName][0];
        }
        // return all the things!
        foreach ($config as $name => $value) {
            $final[$name] = $value[0];
        }
        return $final;
    }

    /**
     * Reset the config to default values
     *
     */
    public function reset()
    {
    }
}
