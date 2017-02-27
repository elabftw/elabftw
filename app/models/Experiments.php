<?php
/**
 * \Elabftw\Elabftw\Experiments
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;
use Exception;

/**
 * All about the experiments
 */
class Experiments extends Entity
{
    /** pdo object */
    public $pdo;

    /** our team */
    public $team;

    /** instance of Links */
    public $Links;

    /** instance of Comments */
    public $Comments;

    /**
     * Constructor
     *
     * @param Users $users
     * @param int|null $id
     */
    public function __construct(Users $users, $id = null)
    {
        $this->pdo = Db::getConnection();

        $this->type = 'experiments';
        $this->Users = $users;

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
        $Templates = new Templates($this->Users);

        // do we want template ?
        if ($tpl) {
            $Templates->setId($tpl);
            $templatesArr = $Templates->read();
            $title = $templatesArr['name'];

        } else {

            $templatesArr = $Templates->readCommon();
            $title = _('Untitled');
        }

        $visibility = 'team';
        if (!is_null($this->Users->userData['default_vis'])) {
            $visibility = $this->Users->userData['default_vis'];
        }

        // SQL for create experiments
        $sql = "INSERT INTO experiments(team, title, date, body, status, elabid, visibility, userid)
            VALUES(:team, :title, :date, :body, :status, :elabid, :visibility, :userid)";
        $req = $this->pdo->prepare($sql);
        $req->execute(array(
            'team' => $this->Users->userData['team'],
            'title' => $title,
            'date' => Tools::kdate(),
            'body' => $templatesArr['body'],
            'status' => $this->getStatus(),
            'elabid' => $this->generateElabid(),
            'visibility' => $visibility,
            'userid' => $this->Users->userid
        ));

        return $this->pdo->lastInsertId();
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
            $this->canOrExplode('read');
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
        $title = Tools::checkTitle($title);
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
        $req->bindParam(':userid', $this->Users->userid);
        $req->bindParam(':id', $this->id);

        // add a revision
        $Revisions = new Revisions($this);

        return $req->execute() && $Revisions->create($body);
    }

    /**
     * Check if we have a correct value
     *
     * @param string $visibility
     * @return bool
     */
    public function checkVisibility($visibility)
    {
        $validArr = array(
            'public',
            'organization',
            'team',
            'user'
        );

        if (in_array($visibility, $validArr)) {
            return true;
        }

        // or we might have a TeamGroup, so an int
        return (bool) Tools::checkId($visibility);
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
        $req->bindParam(':userid', $this->Users->userid);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * Update the status for an experiment
     *
     * @param int $status Id of the status
     * @return bool
     */
    public function updateStatus($status)
    {
        $sql = "UPDATE experiments SET status = :status WHERE userid = :userid AND id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':status', $status);
        $req->bindParam(':userid', $this->Users->userid);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

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
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->execute();
        $status = $req->fetchColumn();

        // if there is no is_default status
        // we take the first status that come
        if (!$status) {
            $sql = 'SELECT id FROM status WHERE team = :team LIMIT 1';
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':team', $this->Users->userData['team']);
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
            'team' => $this->Users->userData['team'],
            'title' => $title,
            'date' => Tools::kdate(),
            'body' => $experiment['body'],
            'status' => $this->getStatus(),
            'elabid' => $this->generateElabid(),
            'visibility' => $experiment['visibility'],
            'userid' => $this->Users->userid));
        $newId = $this->pdo->lastInsertId();

        $tags = new Tags($this);
        $tags->copyTags($newId);

        $this->Links->duplicate($this->id, $newId);

        return (int) $newId;
    }

    /**
     * Destroy an experiment and all associated data
     *
     * @return bool
     */
    public function destroy()
    {
        // delete the experiment
        $sql = "DELETE FROM experiments WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id);
        $req->execute();

        $tags = new Tags($this);
        $tags->destroyAll();

        $uploads = new Uploads($this);
        $uploads->destroyAll();

        $this->Links->destroyAll();

        $this->Comments->destroyAll();

        return true;
    }

    /**
     * Lock/unlock
     *
     * @throws Exception
     * @return bool
     */
    public function toggleLock()
    {
        $locked = (int) $this->entityData['locked'];

        // if we try to unlock something we didn't lock
        if ($locked === 1 && ($this->entityData['lockedby'] != $this->Users->userid)) {
            // Get the first name of the locker to show in error message
            $sql = "SELECT firstname FROM users WHERE userid = :userid";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':userid', $this->entityData['lockedby']);
            $req->execute();
            throw new Exception(
                _('This experiment was locked by') .
                ' ' . $req->fetchColumn() . '. ' .
                _("You don't have the rights to lock/unlock this.")
            );
        }

        // check if the experiment is timestamped. Disallow unlock in this case.
        if ($locked === 1 && $this->entityData['timestamped']) {
            throw new Exception(_('You cannot unlock or edit in any way a timestamped experiment.'));
        }

        // toggle
        if ($locked === 1) {
            $locked = 0;
        } else {
            $locked = 1;
        }
        $sql = "UPDATE experiments
            SET locked = :locked, lockedby = :lockedby, lockedwhen = CURRENT_TIMESTAMP WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':locked', $locked);
        $req->bindParam(':lockedby', $this->Users->userid);
        $req->bindParam(':id', $this->id);

        return $req->execute();
    }
}
