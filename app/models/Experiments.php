<?php
/**
 * \Elabftw\Elabftw\Experiments
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \PDO;
use \Exception;

/**
 * All about the experiments
 */
class Experiments extends Entity
{
    /** pdo object */
    public $pdo;

    /** current user */
    public $userid;

    /** our team */
    public $team;

    /** instance of Links */
    public $Links;

    /** instance of Comments */
    public $Comments;

    /**
     * Constructor
     *
     * @param int $userid
     * @param int|null $id
     */
    public function __construct($userid, $id = null)
    {
        $this->pdo = Db::getConnection();

        $this->userid = $userid;

        $this->team = $_SESSION['team_id'];

        if (!is_null($id)) {
            $this->setId($id);
        }

        $this->Links = new Links($this);
        $this->Comments = new Comments($this);

    }

    /**
     * Create an experiment
     *
     * @param int|null $tpl the template on which to base the experiment
     * @return int the new id of the experiment
     */
    public function create($tpl = null)
    {
        $templates = new Templates($this->team);

        // do we want template ?
        if (Tools::checkId($tpl)) {

            $templatesArr = $templates->read($tpl);
            $title = $templatesArr['name'];

        } else {

            $templatesArr = $templates->readCommon();
            $title = _('Untitled');
        }

        // SQL for create experiments
        $sql = "INSERT INTO experiments(team, title, date, body, status, elabid, visibility, userid) VALUES(:team, :title, :date, :body, :status, :elabid, :visibility, :userid)";
        $req = $this->pdo->prepare($sql);
        $req->execute(array(
            'team' => $this->team,
            'title' => $title,
            'date' => Tools::kdate(),
            'body' => $templatesArr['body'],
            'status' => $this->getStatus(),
            'elabid' => $this->generateElabid(),
            'visibility' => 'team',
            'userid' => $this->userid
        ));

        return $this->pdo->lastInsertId();
    }

    /**
     * Read an experiment
     *
     * @throws Exception if empty results
     * @return array
     */
    public function read()
    {
        $sql = "SELECT DISTINCT experiments.*, status.color, status.name
            FROM experiments
            LEFT JOIN status ON experiments.status = status.id
            LEFT JOIN experiments_tags ON (experiments_tags.item_id = experiments.id)
            WHERE experiments.id = :id ";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->execute();

        if ($req->rowCount() === 0) {
            throw new Exception('Nothing to show with this id.');
        }

        return $req->fetch();
    }

    /**
     * Read all experiments for current user
     *
     * @return array
     */
    public function readAll()
    {
        $sql = "SELECT DISTINCT experiments.*, status.color, status.name
            FROM experiments
            LEFT JOIN status ON (status.team = experiments.team)
            LEFT JOIN experiments_tags ON (experiments_tags.item_id = experiments.id)
            WHERE experiments.userid = :userid
            AND experiments.status = status.id
            " . $this->categoryFilter . "
            " . $this->tagFilter . "
            " . $this->queryFilter . "
            ORDER BY " . $this->order . " " . $this->sort;
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $_SESSION['userid'], PDO::PARAM_INT);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Read all experiments related to a DB item
     *
     * @param int $itemId the DB item
     * @return array
     */
    public function readRelated($itemId)
    {
        $itemsArr = array();

        // get the id of related experiments
        $sql = "SELECT item_id FROM experiments_links
            WHERE link_id = :link_id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':link_id', $itemId);
        $req->execute();
        while ($data = $req->fetch()) {
            $this->setId($data['item_id']);
            $itemsArr[] = $this->read();
        }

        return $itemsArr;
    }

    /**
     * Update an experiment
     *
     * @param string $title
     * @param string $date
     * @param string $body
     * @return bool
     */
    public function update($title, $date, $body)
    {
        $title = check_title($title);
        $date = Tools::kdate($date);
        $body = Tools::checkBody($body);

        $sql = "UPDATE experiments SET
            title = :title,
            date = :date,
            body = :body
            WHERE userid = :userid
            AND id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':title', $title);
        $req->bindParam(':date', $date);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->userid);
        $req->bindParam(':id', $this->id);

        // add a revision
        $revisions = new Revisions($this->id, 'experiments');
        if (!$revisions->create($body, $this->userid)) {
            throw new Exception(_('Error inserting revision.'));
        }

        return $req->execute();
    }

    /**
     * Update the visibility for an experiment
     *
     * @param string $visibility
     * @return bool
     */
    public function updateVisibility($visibility)
    {
        $sql = "UPDATE experiments SET visibility = :visibility WHERE userid = :userid AND id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':visibility', $visibility);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * Update the status for an experiment
     *
     * @param int $experiment Id of the experiment
     * @param int $status Id of the status
     * @param int $userid Id of current user
     * @return string 0 on fail and color of new status on success
     */
    public function updateStatus($status)
    {
        $sql = "UPDATE experiments SET status = :status WHERE userid = :userid AND id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':status', $status, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($req->execute()) {
            // get the color of the status to return and update the css
            $statusClass = new \Elabftw\Elabftw\Status();
            return $statusClass->readColor($status);
        } else {
            return '0';
        }
    }

    /**
     * Select what will be the status for the experiment
     *
     * @return int The status ID
     */
    private function getStatus()
    {
        // what will be the status ?
        // go pick what is the default status upon creating experiment
        // there should be only one because upon making a status default,
        // all the others are made not default
        $sql = 'SELECT id FROM status WHERE is_default = true AND team = :team LIMIT 1';
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $_SESSION['team_id']);
        $req->execute();
        $status = $req->fetchColumn();

        // if there is no is_default status
        // we take the first status that come
        if (!$status) {
            $sql = 'SELECT id FROM status WHERE team = :team LIMIT 1';
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':team', $_SESSION['team_id']);
            $req->execute();
            $status = $req->fetchColumn();
        }
        return $status;
    }

    /**
     * Generate unique elabID
     * This function is called during the creation of an experiment.
     *
     * @return string unique elabid with date in front of it
     */
    private function generateElabid()
    {
        $date = Tools::kdate();
        return $date . "-" . sha1(uniqid($date, true));
    }

    /**
     * Duplicate an experiment
     *
     * @return int Will return the ID of the new item
     */
    public function duplicate()
    {
        $experiment = $this->read();

        // let's add something at the end of the title to show it's a duplicate
        // capital i looks good enough
        $title = $experiment['title'] . ' I';

        $sql = "INSERT INTO experiments(team, title, date, body, status, elabid, visibility, userid)
            VALUES(:team, :title, :date, :body, :status, :elabid, :visibility, :userid)";
        $req = $this->pdo->prepare($sql);
        $req->execute(array(
            'team' => $_SESSION['team_id'],
            'title' => $title,
            'date' => Tools::kdate(),
            'body' => $experiment['body'],
            'status' => $this->getStatus(),
            'elabid' => $this->generateElabid(),
            'visibility' => $experiment['visibility'],
            'userid' => $_SESSION['userid']));
        $newId = $this->pdo->lastInsertId();

        $tags = new Tags('experiments');
        $tags->copyTags($this->id, $newId);
        $this->Links->duplicate($this->id, $newId);

        return $newId;
    }

    /**
     * Destroy an experiment and all associated data
     *
     * @return null
     */
    public function destroy()
    {
        if (((get_team_config('deletable_xp') == '0') &&
            !$_SESSION['is_admin']) ||
            !is_owned_by_user($this->id, 'experiments', $_SESSION['userid'])) {
            throw new Exception(_("You don't have the rights to delete this experiment."));
        }
        // delete the experiment
        $sql = "DELETE FROM experiments WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id);
        $req->execute();

        $tags = new Tags('experiments');
        $tags->destroy($this->id);

        $uploads = new Uploads('experiments', $this->id);
        $uploads->destroy();

        $this->Links->destroyAllLinks();

        $this->Comments->destroyAllComments();

        return true;
    }
}
