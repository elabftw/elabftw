<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CreateInterface;
use PDO;

/**
 * All about the experiments
 */
class Experiments extends AbstractEntity implements CreateInterface
{
    /** @var Links $Links instance of Links */
    public $Links;

    /** @var Steps $Steps instance of Steps */
    public $Steps;

    /**
     * Constructor
     *
     * @param Users $users
     * @param int|null $id
     */
    public function __construct(Users $users, ?int $id = null)
    {
        parent::__construct($users, $id);
        $this->page = 'experiments';
        $this->type = 'experiments';

        $this->Links = new Links($this);
        $this->Steps = new Steps($this);
    }

    /**
     * Create an experiment
     *
     * @param int $tpl the template on which to base the experiment
     * @return int the new id of the experiment
     */
    public function create(int $tpl): int
    {
        $Templates = new Templates($this->Users);

        // do we want template ?
        if ($tpl > 0) {
            $Templates->setId($tpl);
            $templatesArr = $Templates->read();
            $title = $templatesArr['name'];
            $body = $templatesArr['body'];
        } else {
            $title = _('Untitled');
            $body = $Templates->readCommonBody();
        }

        $visibility = 'team';
        if ($this->Users->userData['default_vis'] !== null) {
            $visibility = $this->Users->userData['default_vis'];
        }

        // SQL for create experiments
        $sql = "INSERT INTO experiments(team, title, date, body, category, elabid, visibility, userid)
            VALUES(:team, :title, :date, :body, :category, :elabid, :visibility, :userid)";
        $req = $this->Db->prepare($sql);
        $req->execute(array(
            'team' => $this->Users->userData['team'],
            'title' => $title,
            'date' => Tools::kdate(),
            'body' => $body,
            'category' => $this->getStatus(),
            'elabid' => $this->generateElabid(),
            'visibility' => $visibility,
            'userid' => $this->Users->userData['userid']
        ));
        $newId = $this->Db->lastInsertId();

        // insert the tags from the template
        if ($tpl !== null && $tpl !== 0) {
            $Tags = new Tags($Templates);
            $Tags->copyTags($newId, true);
        }

        return $newId;
    }

    /**
     * Read all experiments related to a DB item
     *
     * @param int $itemId the DB item
     * @return array
     */
    public function readRelated(int $itemId): array
    {
        $itemsArr = array();

        // get the id of related experiments
        $sql = "SELECT item_id FROM experiments_links
            WHERE link_id = :link_id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':link_id', $itemId, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        while ($data = $req->fetch()) {
            $this->setId((int) $data['item_id']);
            $this->canOrExplode('read');
            $itemsArr[] = $this->read();
        }

        return $itemsArr;
    }

    /**
     * Can this experiment be timestamped?
     *
     * @return bool
     */
    public function isTimestampable(): bool
    {
        $currentCategory = (int) $this->entityData['category_id'];
        $sql = "SELECT is_timestampable FROM status WHERE id = :category;";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':category', $currentCategory, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        return (bool) $req->fetchColumn();
    }

    /**
     * Set the experiment as timestamped with a path to the token
     *
     * @param string $responseTime the date of the timestamp
     * @param string $responsefilePath the file path to the timestamp token
     * @return void
     */
    public function updateTimestamp(string $responseTime, string $responsefilePath): void
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
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Select what will be the status for the experiment
     *
     * @return int The status ID
     */
    private function getStatus(): int
    {
        // what will be the status ?
        // go pick what is the default status upon creating experiment
        // there should be only one because upon making a status default,
        // all the others are made not default
        $sql = 'SELECT id FROM status WHERE is_default = true AND team = :team LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        $status = $req->fetchColumn();

        // if there is no is_default status
        // we take the first status that come
        if (!$status) {
            $sql = 'SELECT id FROM status WHERE team = :team LIMIT 1';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
            if ($req->execute() !== true) {
                throw new DatabaseErrorException('Error while executing SQL query.');
            }
            $status = $req->fetchColumn();
        }
        return (int) $status;
    }

    /**
     * Generate unique elabID
     * This function is called during the creation of an experiment.
     *
     * @return string unique elabid with date in front of it
     */
    private function generateElabid(): string
    {
        $date = Tools::kdate();
        return $date . "-" . \sha1(\bin2hex(\random_bytes(16)));
    }

    /**
     * Duplicate an experiment
     *
     * @return int the ID of the new item
     */
    public function duplicate(): int
    {
        // let's add something at the end of the title to show it's a duplicate
        // capital i looks good enough
        $title = $this->entityData['title'] . ' I';

        $sql = "INSERT INTO experiments(team, title, date, body, category, elabid, visibility, userid)
            VALUES(:team, :title, :date, :body, :category, :elabid, :visibility, :userid)";
        $req = $this->Db->prepare($sql);
        $req->execute(array(
            'team' => $this->Users->userData['team'],
            'title' => $title,
            'date' => Tools::kdate(),
            'body' => $this->entityData['body'],
            'category' => $this->getStatus(),
            'elabid' => $this->generateElabid(),
            'visibility' => $this->entityData['visibility'],
            'userid' => $this->Users->userData['userid']));
        $newId = $this->Db->lastInsertId();

        $this->Links->duplicate($this->id, $newId);
        $this->Steps->duplicate($this->id, $newId);
        $this->Tags->copyTags($newId);

        return $newId;
    }

    /**
     * Destroy an experiment and all associated data
     * The foreign key constraints will take care of associated tables
     *
     * @return void
     */
    public function destroy(): void
    {
        $this->canOrExplode('write');

        $this->Tags->destroyAll();
        $this->Uploads->destroyAll();

        $sql = "DELETE FROM experiments WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Get token and pdf info for displaying in view mode
     *
     * @return array
     */
    public function getTimestampInfo(): array
    {
        if ($this->entityData['timestamped'] === '0') {
            return array();
        }
        $timestamper = $this->Users->read((int) $this->entityData['timestampedby']);

        $Uploads = new Uploads(new Experiments($this->Users, (int) $this->entityData['id']));
        $Uploads->Entity->type = 'exp-pdf-timestamp';
        $pdf = $Uploads->readAll();

        $Uploads->Entity->type = 'timestamp-token';
        $token = $Uploads->readAll();

        return array(
            'timestamper' => $timestamper,
            'pdf' => $pdf,
            'token' => $token
        );
    }

    /**
     * Lock/unlock
     *
     * @return void
     */
    public function toggleLock(): void
    {
        $permissions = $this->getPermissions();
        if (!$this->Users->userData['can_lock'] && !$permissions['write']) {
            throw new ImproperActionException(_("You don't have the rights to lock/unlock this."));
        }
        $locked = (int) $this->entityData['locked'];

        // if we try to unlock something we didn't lock
        if ($locked === 1 && ($this->entityData['lockedby'] != $this->Users->userData['userid'])) {
            // Get the first name of the locker to show in error message
            $sql = "SELECT firstname FROM users WHERE userid = :userid";
            $req = $this->Db->prepare($sql);
            $req->bindParam(':userid', $this->entityData['lockedby'], PDO::PARAM_INT);
            if ($req->execute() !== true) {
                throw new DatabaseErrorException('Error while executing SQL query.');
            }
            throw new ImproperActionException(
                _('This experiment was locked by') .
                ' ' . $req->fetchColumn() . '. ' .
                _("You don't have the rights to unlock this.")
            );
        }

        // check if the experiment is timestamped. Disallow unlock in this case.
        if ($locked === 1 && $this->entityData['timestamped']) {
            throw new ImproperActionException(_('You cannot unlock or edit in any way a timestamped experiment.'));
        }

        $sql = "UPDATE experiments SET locked = IF(locked = 1, 0, 1), lockedby = :lockedby, lockedwhen = CURRENT_TIMESTAMP WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':lockedby', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }
}
