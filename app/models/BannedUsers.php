<?php
/**
 * \Elabftw\Elabftw\BannedUsers
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
 * Deal with login rate limiter
 */
class BannedUsers
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
     * Add a banned user
     *
     * @param string $fingerprint Should be the md5 of IP + useragent
     * @return bool
     */
    public function create($fingerprint)
    {
        $sql = "INSERT INTO banned_users (user_infos) VALUES (:user_infos)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':user_infos', $fingerprint);

        return $req->execute();
    }

    /**
     * Select all actively banned users
     *
     * @return array
     */
    public function readAll()
    {
        $banTime = date("Y-m-d H:i:s", strtotime('-' . get_config('ban_time') . ' minutes'));

        $sql = "SELECT user_infos FROM banned_users WHERE time > :ban_time";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':ban_time', $banTime);
        $req->execute();

        return $req->fetchAll(PDO::FETCH_COLUMN);
    }
}
