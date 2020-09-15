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
use Elabftw\Elabftw\ParamsProcessor;
use Elabftw\Elabftw\Tools;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Services\Email;
use PDO;
use Swift_Message;
use Symfony\Component\HttpFoundation\Request;

/**
 * All about the comments
 */
class Comments implements CrudInterface
{
    /** @var AbstractEntity $Entity instance of Experiments or Database */
    public $Entity;

    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var Email $Email instance of Email */
    private $Email;

    /**
     * Constructor
     *
     * @param AbstractEntity $entity
     * @param Email $email
     */
    public function __construct(AbstractEntity $entity, Email $email)
    {
        $this->Db = Db::getConnection();
        $this->Entity = $entity;
        $this->Email = $email;
    }

    /**
     * Create a comment
     */
    public function create(ParamsProcessor $params): int
    {
        $sql = 'INSERT INTO ' . $this->Entity->type . '_comments(datetime, item_id, comment, userid)
            VALUES(:datetime, :item_id, :comment, :userid)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':datetime', date('Y-m-d H:i:s'));
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':comment', $params->comment);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);

        $this->Db->execute($req);

        $this->alertOwner();

        return $this->Db->lastInsertId();
    }

    /**
     * Read comments for an entity id
     *
     * @return array comments for this entity
     */
    public function read(): array
    {
        $sql = 'SELECT ' . $this->Entity->type . "_comments.*,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname
            FROM " . $this->Entity->type . '_comments
            LEFT JOIN users ON (' . $this->Entity->type . '_comments.userid = users.userid)
            WHERE item_id = :id ORDER BY ' . $this->Entity->type . '_comments.datetime ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Update a comment
     */
    public function update(ParamsProcessor $params): string
    {
        $sql = 'UPDATE ' . $this->Entity->type . '_comments SET
            comment = :comment
            WHERE id = :id AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':comment', $params->comment, PDO::PARAM_STR);
        $req->bindParam(':id', $params->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $params->comment;
    }

    /**
     * Destroy a comment
     */
    public function destroy(int $id): bool
    {
        $sql = 'DELETE FROM ' . $this->Entity->type . '_comments WHERE id = :id AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * Send an email to the experiment owner to alert a comment was posted
     * (issue #160). Only send for an experiment.
     *
     * @return int number of email sent
     */
    private function alertOwner(): int
    {
        $Config = new Config();

        // don't do it for Db items or if email is not configured
        if ($this->Entity instanceof Database || $Config->configArr['mail_from'] === 'notconfigured@example.com') {
            return 0;
        }

        // get the first and lastname of the commenter
        $sql = "SELECT CONCAT(firstname, ' ', lastname) AS fullname FROM users WHERE userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $commenter = $req->fetch();

        // get email of the XP owner
        $sql = "SELECT email, userid, CONCAT(firstname, ' ', lastname) AS fullname FROM users
            WHERE userid = (SELECT userid FROM experiments WHERE id = :id)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $users = $req->fetch();

        // don't send an email if we are commenting on our own XP
        if ($users['userid'] === $this->Entity->Users->userData['userid']) {
            return 1;
        }

        // Create the message
        $Request = Request::createFromGlobals();
        $url = Tools::getUrl($Request) . '/' . $this->Entity->page . '.php';
        // not pretty but gets the job done
        $url = str_replace('app/controllers/', '', $url);
        $url .= '?mode=view&id=' . $this->Entity->id;

        $footer = "\n\n~~~\nSent from eLabFTW https://www.elabftw.net\n";

        $message = (new Swift_Message())
        // Give the message a subject
        ->setSubject(_('[eLabFTW] New comment posted'))
        // Set the From address with an associative array
        ->setFrom(array($Config->configArr['mail_from'] => 'eLabFTW'))
        // Set the To addresses with an associative array
        ->setTo(array($users['email'] => $users['fullname']))
        // Give it a body
        ->setBody(sprintf(
            _('Hi. %s left a comment on your experiment. Have a look: %s'),
            $commenter['fullname'],
            $url
        ) . $footer);

        return $this->Email->send($message);
    }
}
