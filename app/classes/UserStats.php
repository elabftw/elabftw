<?php
/**
 * \Elabftw\Elabftw\UserStats
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;


/**
 * Generate and display experiments statistics for a user
 */
class UserStats
{
    /** @var Users $Users instance of Users */
    private $Users;

    /** @var int $count count of experiments */
    private $count = 0;

    /** @var Db $Db SQL Database */
    private $Db;

    /** @var array $countArr status id and count */
    private $countArr = array();

    /** @var array $statusArr status id and name */
    private $statusArr = array();

    /** @var array $colorsArr colors for status */
    public $colorsArr = array();

    /** @var array $percentArr percentage and status name */
    public $percentArr = array();

    /**
     * Init the object with a userid and the total count of experiments
     *
     * @param Users $users
     * @param int $count total count of experiments
     */
    public function __construct(Users $users, $count)
    {
        $this->Users = $users;
        $this->count = $count;
        $this->Db = Db::getConnection();
        $this->countStatus();
        $this->makePercent();
    }

    /**
     * Count number of experiments for each status
     *
     */
    private function countStatus()
    {
        // get all status name and id
        $Status = new Status($this->Users);
        $statusAll = $Status->readAll();

        // populate arrays
        foreach ($statusAll as $status) {
            $this->statusArr[$status['category_id']] = $status['category'];
            $this->colorsArr[] = $status['color'];
        }

        // count experiments for each status
        foreach ($this->statusArr as $key => $value) {
            $sql = "SELECT COUNT(*)
                FROM experiments
                WHERE userid = :userid
                AND status = :status";
            $req = $this->Db->prepare($sql);
            $req->bindParam(':userid', $this->Users->userid);
            $req->bindParam(':status', $key);
            $req->execute();
            $this->countArr[$key] = $req->fetchColumn();
        }
    }

    /**
     * Create an array with status name => percent
     *
     */
    private function makePercent()
    {
        foreach ($this->statusArr as $key => $value) {
            $value = str_replace("'", "\'", html_entity_decode($value, ENT_QUOTES));
            $this->percentArr[$value] = round(($this->countArr[$key] / $this->count) * 100);
        }
    }
}
