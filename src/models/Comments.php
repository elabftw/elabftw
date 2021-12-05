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
use Elabftw\Elabftw\Tools;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Services\Email;
use Elabftw\Traits\SetIdTrait;
use function nl2br;
use PDO;
use Swift_Message;
use Symfony\Component\HttpFoundation\Request;

/**
 * All about the comments
 */
class Comments implements CrudInterface
{
    use SetIdTrait;

    protected Db $Db;

    public function __construct(public AbstractEntity $Entity, private Email $Email, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->id = $id;
    }

    public function create(ContentParamsInterface $params): int
    {
        $sql = 'INSERT INTO ' . $this->Entity->type . '_comments(datetime, item_id, comment, userid)
            VALUES(:datetime, :item_id, :content, :userid)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':datetime', date('Y-m-d H:i:s'));
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':content', nl2br($params->getContent()));
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);

        $this->Db->execute($req);

        $this->alertOwner();

        return $this->Db->lastInsertId();
    }

    public function read(ContentParamsInterface $params): array
    {
        $sql = 'SELECT ' . $this->Entity->type . "_comments.*,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname
            FROM " . $this->Entity->type . '_comments
            LEFT JOIN users ON (' . $this->Entity->type . '_comments.userid = users.userid)
            WHERE item_id = :id ORDER BY ' . $this->Entity->type . '_comments.datetime ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetchAll($req);
    }

    public function update(ContentParamsInterface $params): bool
    {
        $this->Entity->canOrExplode('read');
        $sql = 'UPDATE ' . $this->Entity->type . '_comments SET
            comment = :content
            WHERE id = :id AND userid = :userid AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', nl2br($params->getContent()), PDO::PARAM_STR);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function destroy(): bool
    {
        $sql = 'DELETE FROM ' . $this->Entity->type . '_comments WHERE id = :id AND userid = :userid AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);

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
        $Config = Config::getConfig();

        // don't do it for Db items or if email is not configured
        if ($this->Entity instanceof Items || $Config->configArr['mail_from'] === 'notconfigured@example.com') {
            return 0;
        }

        // get the first and lastname of the commenter
        $sql = "SELECT CONCAT(firstname, ' ', lastname) AS fullname FROM users WHERE userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $commenter = $req->fetch();

        // get email, name and lang of the XP owner
        $sql = "SELECT email, userid, lang, CONCAT(firstname, ' ', lastname) AS fullname FROM users
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
        $url = Tools::getUrl($Request);
        $bodyUrl = $url . '/' . $this->Entity->page . '.php';
        // not pretty but gets the job done
        $bodyUrl = str_replace('app/controllers/', '', $bodyUrl);
        $bodyUrl .= '?mode=view&id=' . $this->Entity->id;

        // set the lang to the target user, not the one commenting (see issue #2700)
        $locale = $users['lang'] . '.utf8';
        // configure gettext
        $domain = 'messages';
        putenv("LC_ALL=$locale");
        setlocale(LC_ALL, $locale);
        bindtextdomain($domain, dirname(__DIR__, 2) . '/src/langs');
        bind_textdomain_codeset($domain, 'UTF-8');
        textdomain($domain);
        // END i18n

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
            $bodyUrl
        ) . $this->Email->footer);

        return $this->Email->send($message);
    }
}
