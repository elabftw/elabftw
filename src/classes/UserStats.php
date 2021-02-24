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

use Elabftw\Models\Status;
use Elabftw\Models\Users;
use PDO;

/**
 * Generate experiments statistics for a user (shown on profile page)
 */
class UserStats
{
    /** @var array $colorsArr colors for status */
    public $colorsArr = array();

    /** @var array $percentArr percentage and status name */
    public $percentArr = array();

    /** @var Users $Users instance of Users */
    private $Users;

    /** @var int $count count of experiments */
    private $count;

    /** @var Db $Db SQL Database */
    private $Db;

    /** @var array $countArr status id and count */
    private $countArr = array();

    /** @var array $statusArr status id and name */
    private $statusArr = array();

    /**
     * Init the object with a userid and the total count of experiments
     *
     * @param Users $users
     * @param int $count total count of experiments
     */
    public function __construct(Users $users, int $count)
    {
        $this->Users = $users;
        $this->count = $count;
        $this->Db = Db::getConnection();
    }

    /**
     * Create the statistics
     *
     * @return void
     */
    public function makeStats(): void
    {
        // only work if there is something to work on
        if ($this->count > 0) {
            $this->countStatus();
            $this->makePercent();
        }
    }

    /**
     * Count number of experiments for each status
     *
     * @return void
     */
    private function countStatus(): void
    {
        // get all status name and id
        $Status = new Status($this->Users);
        $statusAll = $Status->read();

        // populate arrays
        foreach ($statusAll as $status) {
            $this->statusArr[$status['category_id']] = $status['category'];
            $this->colorsArr[] = '#' . $status['color'];
        }

        // count experiments for each status
        foreach (array_keys($this->statusArr) as $key) {
            $sql = 'SELECT COUNT(id)
                FROM experiments
                WHERE userid = :userid
                AND category = :category';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
            $req->bindParam(':category', $key, PDO::PARAM_INT);
            $req->execute();
            $this->countArr[$key] = $req->fetchColumn();
        }
    }

    /**
     * Create an array with status name => percent
     *
     * @return void
     */
    private function makePercent(): void
    {
        foreach ($this->statusArr as $key => $value) {
            $value = str_replace("'", "\'", html_entity_decode($value, ENT_QUOTES));
            $this->percentArr[$value] = round(((float) $this->countArr[$key] / (float) $this->count) * 100.0);
        }
    }
}
