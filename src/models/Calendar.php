<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Traits\EntityTrait;
use PDO;

/**
 * All about the user's calendar
 */
class Calendar
{
    use EntityTrait;

    /** @var Users $Users instance of Users */
    public $Users;

    /**
     * Constructor
     *
     * @param Users $users
     */
    public function __construct(Users $users)
    {
        $this->Db = Db::getConnection();
        $this->Users = $users;
    }

    /**
     * creates an Event
     *
     * @param string $start
     * @param string $end
     * @param string $title
     * @param string $stepId
     * @return integer
     */
    public function createEvent(string $start, string $end, string $title): int
    {
        $title = filter_var($title, FILTER_SANITIZE_STRING);
        $sql = 'INSERT INTO team_events(team, start, end, userid, title)
            VALUES(:team, :start, :end, :userid, :title)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':start', $start);
        $req->bindParam(':end', $end);
        $req->bindParam(':title', $title);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);

        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }

    public function readAllFromUser(string $start, string $end): array
    {
        // the title of the event is title + Firstname Lastname of the user who booked it
        $sql = 'SELECT team_events.title, team_events.id, team_events.start, team_events.end, team_events.userid
        FROM team_events
        LEFT JOIN users AS u ON team_events.userid = u.userid
        WHERE u.userid = :userid
        AND team_events.start > :start AND team_events.end <= :end';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':start', $start);
        $req->bindParam(':end', $end);
        $this->Db->execute($req);

        $res = $req->fetchAll();

        if ($res === false) {
            return array();
        }
        return $res;
    }
}
