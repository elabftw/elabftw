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
    private Users $Users;

    private int $count;

    private Db $Db;

    public function __construct(Users $users, int $count)
    {
        $this->Users = $users;
        $this->count = $count;
        $this->Db = Db::getConnection();
    }

    /**
     * Generate data for pie chart of status
     * We want an array with each value corresponding to a status with: name, percent and color
     */
    public function getPieData(): array
    {
        // get all status name and id
        $Status = new Status($this->Users);
        $statusAll = $Status->read();

        $res = array();

        // populate arrays
        foreach ($statusAll as $status) {
            $statusArr = array();
            $statusArr['name'] = $status['category'];
            $statusArr['id'] = $status['category_id'];
            $statusArr['color'] = '#' . $status['color'];

            // now get the count
            $sql = 'SELECT COUNT(id)
                FROM experiments
                WHERE userid = :userid
                AND category = :category';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
            $req->bindParam(':category', $status['category_id'], PDO::PARAM_INT);
            $req->execute();
            $statusArr['count'] = $req->fetchColumn();

            // calculate the percent
            $statusArr['percent'] = round(((float) $statusArr['count'] / (float) $this->count) * 100.0);

            $res[] = $statusArr;
        }
        return $res;
    }

    /**
     * Take the raw data and make a string that can be injected into conic-gradient css value
     * example: #29AEB9 18%,#54AA08 0 43%,#C0C0C0 0 74%,#C24F3D 0
     */
    public function getFormattedPieData(): string
    {
        $pieData = $this->getPieData();
        $res = '';
        $percentSum = 0;
        foreach ($pieData as $key => $value) {
            if ($key === array_key_first($pieData)) {
                $res .= $value['color'] . ' ' . $value['percent'] . '%,';
                $percentSum = $value['percent'];
                continue;
            }

            // last one is just 0
            if ($key === array_key_last($pieData)) {
                $res .= $value['color'] . ' 0';
                continue;
            }
            // the percent value needs to be added to the previous sum of percents
            $percentSum += $value['percent'];
            $res .= $value['color'] . ' 0 ' . $percentSum . '%,';
        }
        return $res;
    }
}
