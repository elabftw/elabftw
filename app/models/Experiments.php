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
class Experiments extends AbstractEntity
{
    use EntityTrait;

    /** @var Comments $Comments instance of Comments */
    public $Comments;

    /** @var Links $Links instance of Links */
    public $Links;

    /** @var Steps $Steps instance of Steps */
    public $Steps;

    /** @var string $page The page name */
    public $page = 'experiments';

    /** @var string $type The table/type name TODO remove type and check with instanceof, rename table because it's used as table */
    public $type = 'experiments';

    /**
     * Constructor
     *
     * @param Users $users
     * @param int|null $id
     */
    public function __construct(Users $users, $id = null)
    {
        parent::__construct($users, $id);

        $this->Comments = new Comments($this);
        $this->Links = new Links($this);
        $this->Steps = new Steps($this);
    }

    /**
     * Create an experiment
     *
     * @param int|null $tpl the template on which to base the experiment
     * @return string the new id of the experiment
     */
    public function create($tpl = null)
    {
        $Templates = new Templates($this->Users);

        // do we want template ?
        if ($tpl !== null) {
            $Templates->setId($tpl);
            $templatesArr = $Templates->read();
            $title = $templatesArr['name'];
            $body = $templatesArr['body'];
        } else {
            $title = _('Untitled');
            $body = $Templates->readCommonBody();
        }

        $visibility = 'team';
        if (!is_null($this->Users->userData['default_vis'])) {
            $visibility = $this->Users->userData['default_vis'];
        }

        // SQL for create experiments
        $sql = "INSERT INTO experiments(team, title, date, body, status, elabid, visibility, userid)
            VALUES(:team, :title, :date, :body, :status, :elabid, :visibility, :userid)";
        $req = $this->Db->prepare($sql);
        $req->execute(array(
            'team' => $this->Users->userData['team'],
            'title' => $title,
            'date' => Tools::kdate(),
            'body' => $body,
            'status' => $this->getStatus(),
            'elabid' => $this->generateElabid(),
            'visibility' => $visibility,
            'userid' => $this->Users->userid
        ));
        $newId = $this->Db->lastInsertId();

        // insert the tags from the template
        if ($tpl !== null) {
            $Tags = new Tags(new Templates($this->Users, $tpl));
            $Tags->copyTags($newId);
        }

        return $newId;
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
        $req = $this->Db->prepare($sql);
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
        $sql = "UPDATE experiments SET visibility = :visibility WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':visibility', $visibility);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * Update the status for an experiment
     *
     * @param int $status Id of the status
     * @return bool
     */
    public function updateCategory($status)
    {
        $sql = "UPDATE experiments SET status = :status WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':status', $status);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $req->execute();
    }

    /**
    * Returns if this experiment can be timestamped
    * It checks if the status is timestampable but also if we own the experiment
    *
    * @return string 0 or 1
    */
    public function isTimestampable()
    {
        $currentStatus = (int) $this->entityData['category_id'];
        $sql = "SELECT is_timestampable FROM status WHERE id = :status;";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':status', $currentStatus);
        $req->execute();
        return $req->fetchColumn();
    }

    /**
     * Set the experiment as timestamped with a path to the token
     *
     * @param string $responseTime the date of the timestamp
     * @param string $responsefilePath the file path to the timestamp token
     * @return bool
     */
    public function updateTimestamp($responseTime, $responsefilePath)
    {
        $sql = "UPDATE experiments SET
            locked = 1,
            lockedby = :userid,
            lockedwhen = :when,
            timestamped = 1,
            timestampedby = :userid,
            timestampedwhen = :when,
            timestamptoken = :longname
            WHERE id = :id;";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':when', $responseTime);
        // the date recorded in the db has to match the creation time of the timestamp token
        $req->bindParam(':longname', $responsefilePath);
        $req->bindParam(':userid', $this->Users->userid);
        $req->bindParam(':id', $this->id);

        return $req->execute();
    }

    /**
     * Select what will be the status for the experiment
     *
     * @return string The status ID
     */
    private function getStatus()
    {
        // what will be the status ?
        // go pick what is the default status upon creating experiment
        // there should be only one because upon making a status default,
        // all the others are made not default
        $sql = 'SELECT id FROM status WHERE is_default = true AND team = :team LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->execute();
        $status = $req->fetchColumn();

        // if there is no is_default status
        // we take the first status that come
        if (!$status) {
            $sql = 'SELECT id FROM status WHERE team = :team LIMIT 1';
            $req = $this->Db->prepare($sql);
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
        $req = $this->Db->prepare($sql);
        $req->execute(array(
            'team' => $this->Users->userData['team'],
            'title' => $title,
            'date' => Tools::kdate(),
            'body' => $experiment['body'],
            'status' => $this->getStatus(),
            'elabid' => $this->generateElabid(),
            'visibility' => $experiment['visibility'],
            'userid' => $this->Users->userid));
        $newId = $this->Db->lastInsertId();

        $this->Links->duplicate($this->id, $newId);
        $this->Steps->duplicate($this->id, $newId);
        $this->Tags->copyTags($newId);

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
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id);
        $req->execute();


        $this->Comments->destroyAll();
        $this->Links->destroyAll();
        $this->Steps->destroyAll();
        $this->Tags->destroyAll();
        $this->Uploads->destroyAll();


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
            $req = $this->Db->prepare($sql);
            $req->bindParam(':userid', $this->entityData['lockedby']);
            $req->execute();
            throw new Exception(
                _('This experiment was locked by') .
                ' ' . $req->fetchColumn() . '. ' .
                _("You don't have the rights to unlock this.")
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
        $req = $this->Db->prepare($sql);
        $req->bindParam(':locked', $locked);
        $req->bindParam(':lockedby', $this->Users->userid);
        $req->bindParam(':id', $this->id);

        return $req->execute();
    }
}
