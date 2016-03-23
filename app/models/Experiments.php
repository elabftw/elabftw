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
    protected $pdo;

    /** id of the experiment */
    public $id;

    /** current user */
    private $userid;

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

        if (!is_null($id)) {
            $this->setId($id);
        }

    }

    /**
     * Create an experiment
     *
     * @param int|null $tpl the template on which to base the experiment
     * @return int the new id of the experiment
     */
    public function create($tpl = null)
    {
        // do we want template ?
        if (Tools::checkId($tpl) != false) {
            // SQL to get template
            $sql = "SELECT name, body FROM experiments_templates WHERE id = :id AND team = :team";
            $get_tpl = $this->pdo->prepare($sql);
            $get_tpl->bindParam(':id', $tpl);
            $get_tpl->bindParam(':team', $_SESSION['team_id']);
            $get_tpl->execute();
            $get_tpl_info = $get_tpl->fetch();

            // the title is the name of the template
            $title = $get_tpl_info['name'];
            $body = $get_tpl_info['body'];

        } else {
            // if there is no template, title is 'Untitled' and the body is the default exp_tpl
            // SQL to get body
            $sql = "SELECT body FROM experiments_templates WHERE userid = 0 AND team = :team";
            $get_body = $this->pdo->prepare($sql);
            $get_body->bindParam(':team', $_SESSION['team_id']);
            $get_body->execute();
            $body = $get_body->fetchColumn();
            $title = _('Untitled');
        }

        // SQL for create experiments
        $sql = "INSERT INTO experiments(team, title, date, body, status, elabid, visibility, userid) VALUES(:team, :title, :date, :body, :status, :elabid, :visibility, :userid)";
        $req = $this->pdo->prepare($sql);
        $req->execute(array(
            'team' => $_SESSION['team_id'],
            'title' => $title,
            'date' => kdate(),
            'body' => $body,
            'status' => $this->getStatus(),
            'elabid' => $this->generateElabid(),
            'visibility' => 'team',
            'userid' => $_SESSION['userid']
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
        $sql = "SELECT experiments.*, status.color, status.name FROM experiments
            LEFT JOIN status ON experiments.status = status.id
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
     * Update an experiment
     *
     * @param string $title
     * @param string $date
     * @param string $body
     */
    public function update($title, $date, $body)
    {
        $title = check_title($title);
        $date = check_date($date);
        $body = check_body($body);

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
     * Add a link to an experiment
     *
     * @param int $link ID of database item
     * @param int $experiment ID of the experiment
     * @param int $userid used to check we own the experiment
     * @throws Exception
     * @return bool
     */
    public function createLink($link)
    {
        // check link is int and experiment is owned by user
        $link = Tools::checkId($link);
        if ($link === false) {
            throw new Exception('The id parameter is invalid!');
        }
        if (!is_owned_by_user($this->id, 'experiments', $this->userid)) {
            throw new Exception('This section is out of your reach!');
        }

        $sql = "INSERT INTO experiments_links (item_id, link_id) VALUES(:item_id, :link_id)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':link_id', $link, PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * Get links for an experiments
     *
     * @param int $experiment
     * @return array
     */
    public function readLink()
    {
        $sql = "SELECT items.id AS itemid,
            experiments_links.id AS linkid,
            experiments_links.*,
            items.*,
            items_types.*
            FROM experiments_links
            LEFT JOIN items ON (experiments_links.link_id = items.id)
            LEFT JOIN items_types ON (items.type = items_types.id)
            WHERE experiments_links.item_id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Delete a link
     *
     * @param int $link ID of our link
     * @param int $experiment ID of the experiment
     * @param int $userid used to check we own the experiment
     * @return bool
     */
    public function destroyLink($link)
    {
        if (!Tools::checkId($link) ||
            !is_owned_by_user($this->id, 'experiments', $this->userid)) {
            throw new Exception('Error removing link');
        }
        $sql = "DELETE FROM experiments_links WHERE id= :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $link, PDO::PARAM_INT);

        return $req->execute();
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
     * Generate unique elabID.
     * This function is called during the creation of an experiment.
     *
     * @return string unique elabid with date in front of it
     */
    private function generateElabid()
    {
        $date = kdate();
        return $date . "-" . sha1(uniqid($date, true));
    }

    /**
     * Duplicate an experiment.
     *
     * @param int $id The id of the experiment to duplicate
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
            'date' => kdate(),
            'body' => $experiment['body'],
            'status' => $this->getStatus(),
            'elabid' => $this->generateElabid(),
            'visibility' => $experiment['visibility'],
            'userid' => $_SESSION['userid']));
        $newId = $this->pdo->lastInsertId();

        $tags = new Tags('experiments');
        $tags->copyTags($this->id, $newId);
        $this->copyLinks($this->id, $newId);

        return $newId;
    }

    /**
     * Copy the links from one experiment to an other.
     *
     * @param int $id The id of the original experiment
     * @param int $newId The id of the new experiment that will receive the links
     * @return null
     */
    private function copyLinks($id, $newId)
    {
        // LINKS
        $linksql = "SELECT link_id FROM experiments_links WHERE item_id = :id";
        $linkreq = $this->pdo->prepare($linksql);
        $linkreq->bindParam(':id', $id);
        $linkreq->execute();

        while ($links = $linkreq->fetch()) {
            $sql = "INSERT INTO experiments_links (link_id, item_id) VALUES(:link_id, :item_id)";
            $req = $this->pdo->prepare($sql);
            $req->execute(array(
                'link_id' => $links['link_id'],
                'item_id' => $newId
            ));
        }
    }
}
