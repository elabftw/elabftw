<?php
/**
 * \Elabftw\Elabftw\Comments
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
use \Swift_Message;

/**
 * All about the comments
 */
class Comments
{
    /** pdo object */
    protected $pdo;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

    /**
     * Create a comment
     *
     * @return bool
     */
    public function create($id, $comment, $userid)
    {
        $comment = filter_var($comment, FILTER_SANITIZE_STRING);

        $sql = "INSERT INTO experiments_comments(datetime, exp_id, comment, userid)
            VALUES(:datetime, :exp_id, :comment, :userid)";
        $req = $this->pdo->prepare($sql);
        $req->bindValue(':datetime', date("Y-m-d H:i:s"));
        $req->bindParam(':exp_id', $id);
        $req->bindParam(':comment', $comment);
        $req->bindParam(':userid', $userid);

        if (!$req->execute()) {
            throw new Exception('Error inserting comment!');
        }

        return $this->alertOwner($id);
    }

    /**
     * Send an email to the experiment owner to alert a comment was posted
     * (issue #160)
     *
     * @param int $id Id of the experiment
     */
    private function alertOwner($id)
    {
        // get the first and lastname of the commenter
        $sql = "SELECT firstname, lastname FROM users WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $_SESSION['userid']);
        $req->execute();
        $commenter = $req->fetch();

        // get email of the XP owner
        $sql = "SELECT email, userid, firstname, lastname FROM users
            WHERE userid = (SELECT userid FROM experiments WHERE id = :id)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->execute();
        $users = $req->fetch();

        // don't send an email if we are commenting on our own XP
        if ($users['userid'] === $_SESSION['userid']) {
            return true;
        }

        // Create the message
        $url = 'https://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['PHP_SELF'];
        $url = str_replace('app/controllers/CommentsController.php', 'experiments.php', $url);
        $full_url = $url . "?mode=view&id=" . $id;

        $footer = "\n\n~~~\nSent from eLabFTW http://www.elabftw.net\n";

        $message = Swift_Message::newInstance()
        // Give the message a subject
        ->setSubject(_('[eLabFTW] New comment posted'))
        // Set the From address with an associative array
        ->setFrom(array(get_config('mail_from') => 'eLabFTW'))
        // Set the To addresses with an associative array
        ->setTo(array($users['email'] => $users['firstname'] . $users['lastname']))
        // Give it a body
        ->setBody(sprintf(
            _('Hi. %s %s left a comment on your experiment. Have a look: %s'),
            $commenter['firstname'],
            $commenter['lastname'],
            $full_url
        ) . $footer);
        $mailer = getMailer();

        return $mailer->send($message);
    }
}
