<?php
/**
 * \Elabftw\Elabftw\Comments
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Swift_Message;
use Symfony\Component\HttpFoundation\Request;

/**
 * All about the comments
 */
class Comments implements CrudInterface
{
    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var AbstractEntity $Entity instance of Experiments or Database */
    public $Entity;

    /**
     * Constructor
     *
     * @param AbstractEntity $entity
     */
    public function __construct(AbstractEntity $entity)
    {
        $this->Db = Db::getConnection();
        $this->Entity = $entity;
    }

    /**
     * Create a comment
     *
     * @param string $comment Content for the comment
     * @return int comment id
     */
    public function create(string $comment): int
    {
        $comment = nl2br(filter_var($comment, FILTER_SANITIZE_STRING));

        $sql = 'INSERT INTO ' . $this->Entity->type . '_comments(datetime, item_id, comment, userid)
            VALUES(:datetime, :item_id, :comment, :userid)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':datetime', date('Y-m-d H:i:s'));
        $req->bindParam(':item_id', $this->Entity->id);
        $req->bindParam(':comment', $comment);
        $req->bindParam(':userid', $this->Entity->Users->userid);

        $this->alertOwner();

        $req->execute();

        return $this->Db->lastInsertId();
    }

    /**
     * Send an email to the experiment owner to alert a comment was posted
     * (issue #160). Only send for an experiment.
     *
     * @return int number of email sent
     */
    private function alertOwner(): int
    {
        if ($this->Entity instanceof Database) {
            return 0;
        }

        $Config = new Config();

        // get the first and lastname of the commenter
        $sql = "SELECT CONCAT(firstname, ' ', lastname) AS fullname FROM users WHERE userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Entity->Users->userid);
        $req->execute();
        $commenter = $req->fetch();

        // get email of the XP owner
        $sql = "SELECT email, userid, CONCAT(firstname, ' ', lastname) AS fullname FROM users
            WHERE userid = (SELECT userid FROM experiments WHERE id = :id)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id);
        $req->execute();
        $users = $req->fetch();

        // don't send an email if we are commenting on our own XP
        if ($users['userid'] === $this->Entity->Users->userid) {
            return 1;
        }

        // Create the message
        $Request = Request::createFromGlobals();
        $url = Tools::getUrl($Request) . '/' . $this->Entity->page . '.php';
        // not pretty but gets the job done
        $url = str_replace('app/controllers/', '', $url);
        $url .= "?mode=view&id=" . $this->Entity->id;

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
        $Email = new Email(new Config());
        $mailer = $Email->getMailer();

        return $mailer->send($message);
    }

    /**
     * Read comments for an entity id
     *
     * @return array comments for this entity
     */
    public function readAll(): array
    {
        $sql = "SELECT " . $this->Entity->type . "_comments.*,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname
            FROM " . $this->Entity->type . "_comments
            LEFT JOIN users ON (" . $this->Entity->type . "_comments.userid = users.userid)
            WHERE item_id = :id ORDER BY " . $this->Entity->type . "_comments.datetime ASC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id);
        $req->execute();
        if ($req->rowCount() > 0) {
            return $req->fetchAll();
        }

        return array();
    }

    /**
     * Update a comment
     *
     * @param string $comment New content for the comment
     * @param string $id id of the comment (comment_42)
     * @return bool
     */
    public function update(string $comment, string $id): bool
    {
        $comment = \nl2br(\filter_var($comment, FILTER_SANITIZE_STRING));
        // check length
        if (\mb_strlen($comment) < 2) {
            return false;
        }

        $exploded = \explode('_', $id);
        $id = (int) $exploded[1];

        $sql = 'UPDATE ' . $this->Entity->type . '_comments SET
            comment = :comment
            WHERE id = :id AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':comment', $comment);
        $req->bindParam(':id', $id);
        $req->bindParam(':userid', $this->Entity->Users->userid);

        return $req->execute();
    }

    /**
     * Destroy a comment
     *
     * @param int $id id of the comment
     * @return bool
     */
    public function destroy(int $id): bool
    {
        $sql = 'DELETE FROM ' . $this->Entity->type . '_comments WHERE id = :id AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':userid', $this->Entity->Users->userid);

        return $req->execute();
    }

    /**
     * Destroy all comments of an experiment
     *
     * @return bool
     */
    public function destroyAll(): bool
    {
        $sql = 'DELETE FROM ' . $this->Entity->type . '_comments WHERE item_id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id);

        return $req->execute();
    }
}
