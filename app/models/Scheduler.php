<?php
/**
 * \Elabftw\Elabftw\Scheduler
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;
use Exception;

/**
 * All about the team's scheduler
 */
class Scheduler extends Entity
{
    /** pdo object */
    protected $pdo;

    /** our current team */
    private $team;

    /** our item */
    public $id;

    /**
     * Constructor
     *
     * @param int $team
     */
    public function __construct($team)
    {
        $this->team = $team;
        $this->pdo = Db::getConnection();
    }

    /**
     * Add an event for an item in the team
     *
     * @param string $start 2016-07-22T13:37:00
     * @return bool
     */
    public function create($start) {
        $sql = "INSERT INTO team_events(team, item, start) VALUES(:team, :item, :start)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $this->team);
        $req->bindParam(':item', $this->id);
        $req->bindParam(':start', $start);

        return $req->execute();
    }

    /**
     * Return a JSON string with events for this item
     *
     * @return string JSON
     */
    public function read() {
        $sql = "SELECT * FROM team_events WHERE team = :team AND item = :item";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $this->team);
        $req->bindParam(':item', $this->id);
        $req->execute();

        return json_encode($req->fetchAll());
    }
}
