<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use PDO;

/**
 * Deal with login rate limiter
 */
class BannedUsers
{
    /** @var Config $Config Master configuration */
    public $Config;

    /** @var Db $Db SQL Database */
    protected $Db;

    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->Db = Db::getConnection();
        $this->Config = $config;
    }

    /**
     * Add a banned user
     *
     * @param string $fingerprint Should be the md5 of IP + useragent
     * @return bool
     */
    public function create(string $fingerprint): bool
    {
        $sql = 'INSERT INTO banned_users (fingerprint) VALUES (:fingerprint)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':fingerprint', $fingerprint);

        return $req->execute();
    }

    /**
     * Select all actively banned users
     *
     * @return array
     */
    public function readAll(): array
    {
        $banTime = date('Y-m-d H:i:s', (int) strtotime('-' . $this->Config->configArr['ban_time'] . ' minutes'));

        $sql = 'SELECT fingerprint FROM banned_users WHERE time > :ban_time';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':ban_time', $banTime);
        $req->execute();

        $res = $req->fetchAll(PDO::FETCH_COLUMN);
        if ($res === false) {
            return array();
        }
        return $res;
    }
}
