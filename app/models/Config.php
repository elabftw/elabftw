<?php
/**
 * \Elabftw\Elabftw\Config
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
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

    /** the array with all config */
    public $configArr;

    /**
     * Get pdo and load the configArr
     *
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
        $this->configArr = $this->read();
    }

    /**
     * Read the configuration values
     *
     * @return array
     */
    public function read()
    {
        $final = array();

        $sql = "SELECT * FROM config";
        $req = $this->pdo->prepare($sql);
        $req->execute();
        $config = $req->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);
        foreach ($config as $name => $value) {
            $final[$name] = $value[0];
        }
        return $final;
    }

    /**
     * Reset the timestamp password
     *
     * @return bool
     */
    public function destroyStamppass()
    {
        $sql = "UPDATE config SET conf_value = NULL WHERE conf_name = 'stamppass'";
        $req = $this->pdo->prepare($sql);
        return $req->execute();
    }

    /**
     * Reset the config to default values
     *
     */
    public function reset()
    {
    }
}
