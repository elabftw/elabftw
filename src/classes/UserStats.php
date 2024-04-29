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

use Elabftw\Enums\State;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use PDO;

use function array_key_first;
use function array_key_last;
use function implode;
use function round;
use function sprintf;

/**
 * Generate experiments statistics for a user (shown on profile page)
 */
class UserStats
{
    private Db $Db;

    private array $pieData = array();

    /**
     * @param int $count the number of all experiments of the user with state normal
     */
    public function __construct(private Users $Users, private int $count)
    {
        $this->Db = Db::getConnection();
        $this->readPieDataFromDB();
    }

    public function getPieData(): array
    {
        return $this->pieData;
    }

    /**
     * Take the raw data and make a string that can be injected into conic-gradient css value
     * example: #29AEB9 90deg, #54AA08 0 180deg, #C0C0C0 0 270deg, #C24F3D 0
     */
    public function getFormattedPieData(): string
    {
        $res = array();
        $degSum = 0;
        foreach ($this->pieData as $key => $value) {
            // the degree value needs to be added to the previous sum of degrees
            $degSum += $value['deg'];

            $res[] = sprintf(
                '%s %s %s',
                $value['color'],
                // don't add 0 for first entry
                $key === array_key_first($this->pieData)
                    ? ''
                    : '0',
                // don't add degSum for last entry
                $key === array_key_last($this->pieData)
                    ? ''
                    : "{$degSum}deg",
            );
        }
        return implode(', ', $res);
    }

    /**
     * Generate data for pie chart of status
     * We want an array with each value corresponding to a status with: name, percent and color
     */
    private function readPieDataFromDB(): void
    {
        // prevent division by zero error if user has no experiments
        if ($this->count === 0) {
            return;
        }
        $percentFactor = 100.0 / (float) $this->count;
        $degFactor = 360.0 / (float) $this->count;

        // get all status name and id independent of state
        $statusArr = (new ExperimentsStatus(new Teams($this->Users, $this->Users->team)))->readAllIgnoreState();
        // add "status" for experiments without status
        $statusArr[] = array(
            'title' => _('Not set'),
            'id' => -1,
            'color' => 'bdbdbd',
        );

        // get number of experiments without status
        $req = $this->Db->prepare($this->getSQL(true));
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $req->execute();
        $countExpWithoutStatus = $req->fetchColumn();

        // prepare sql query for experiments with status
        $req = $this->Db->prepare($this->getSQL());
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);

        // populate pieData
        foreach ($statusArr as $status) {
            $this->pieData[] = array();
            $lastKey = array_key_last($this->pieData);
            $this->pieData[$lastKey]['name'] = $status['title'];
            $this->pieData[$lastKey]['id'] = $status['id'];
            $this->pieData[$lastKey]['color'] = '#' . $status['color'];

            if ($status['id'] === -1) {
                $this->pieData[$lastKey]['count'] = $countExpWithoutStatus;
            } else {
                // now get the count
                $req->bindParam(':status', $status['id'], PDO::PARAM_INT);
                $req->execute();
                $this->pieData[$lastKey]['count'] = $req->fetchColumn();
            }

            // calculate the percent and deg
            $this->pieData[$lastKey]['percent'] = round($percentFactor * (float) $this->pieData[$lastKey]['count']);
            $this->pieData[$lastKey]['deg'] = round($degFactor * (float) $this->pieData[$lastKey]['count'], 2);
        }
    }

    /**
     * @param bool $statusIsNull Are we looking for experiments where the status is null
     */
    private function getSQL(bool $statusIsNull = false): string
    {
        return sprintf(
            'SELECT COUNT(id)
                FROM experiments
                WHERE userid = :userid
                    AND state = :state
                    AND status %s',
            $statusIsNull ? 'IS NULL' : '= :status'
        );
    }
}
