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
namespace Elabftw\Elabftw;

use Exception;
use Swift_Message;
use Symfony\Component\HttpFoundation\Request;

/**
 * All about the comments
 */
class Comments implements CrudInterface
{
    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var Experiments $Entity instance of Experiments */
    public $Entity;

    /**
     * Constructor
     *
     * @param Experiments $entity
     */
    public function __construct(Experiments $entity)
    {
        $this->Db = Db::getConnection();
        $this->Entity = $entity;
    }

    /**
     * Create a comment
     *
     * @param string $comment Content for the comment
     * @return int number of email sent
     */
    public function create($comment)
    {
        $comment = filter_var($comment, FILTER_SANITIZE_STRING);

        $sql = "INSERT INTO experiments_comments(datetime, exp_id, comment, userid)
            VALUES(:datetime, :exp_id, :comment, :userid)";
        $req = $this->Db->prepare($sql);
        $req->bindValue(':datetime', date("Y-m-d H:i:s"));
        $req->bindParam(':exp_id', $this->Entity->id);
        $req->bindParam(':comment', $comment);
        $req->bindParam(':userid', $this->Entity->Users->userid);

        if (!$req->execute()) {
            throw new Exception('Error inserting comment!');
        }

        return $this->alertOwner();
    }

    /**
     * Send an email to the experiment owner to alert a comment was posted
     * (issue #160)
     *
     * @return int number of email sent
     */
    private function alertOwner()
    {
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
        $url = 'https://' . $Request->getHttpHost() . '/experiments.php';
        $url .= "?mode=view&id=" . $this->Entity->id;

        $footer = "\n\n~~~\nSent from eLabFTW https://www.elabftw.net\n";

        $message = Swift_Message::newInstance()
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
        $Email = new Email(new Config);
        $mailer = $Email->getMailer();

        return $mailer->send($message);
    }

    /**
     * Read comments for an experiments id
     *
     * @return array|false results or false if no comments
     */
    public function readAll()
    {
        $sql = "SELECT experiments_comments.*,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname
            FROM experiments_comments
            LEFT JOIN users ON (experiments_comments.userid = users.userid)
            WHERE exp_id = :id ORDER BY experiments_comments.datetime ASC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id);
        $req->execute();
        if ($req->rowCount() > 0) {
            return $req->fetchAll();
        }

        return false;
    }

    /**
     * Update a comment
     *
     * @param string $comment New content for the comment
     * @param int $id id of the comment
     * @return bool
     */
    public function update($comment, $id)
    {
        $comment = filter_var($comment, FILTER_SANITIZE_STRING);
        // check length
        if (strlen($comment) < 2) {
            return false;
        }

        $sql = "UPDATE experiments_comments SET
            comment = :comment
            WHERE id = :id AND userid = :userid";
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
    public function destroy($id)
    {
        $sql = "DELETE FROM experiments_comments WHERE id = :id AND userid = :userid";
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
    public function destroyAll()
    {
        $sql = "DELETE FROM experiments_comments WHERE exp_id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id);

        return $req->execute();
    }
}
