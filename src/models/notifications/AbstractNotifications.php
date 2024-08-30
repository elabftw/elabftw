<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Notifications;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Notifications;
use Elabftw\Models\Users;
use Elabftw\Traits\SetIdTrait;
use PDO;

/**
 * Mother class for notifications that can be sent to a user
 */
abstract class AbstractNotifications
{
    use SetIdTrait;

    /** @var non-empty-string */
    protected const PREF = 'not set';

    protected Notifications $category;

    protected Db $Db;

    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    public function create(int $userid): int
    {
        [$webNotif, $sendEmail] = $this->getPref($userid);

        $isAck = 1;
        if ($webNotif === 1) {
            $isAck = 0;
        }

        $jsonBody = json_encode($this->getBody(), JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT, 5);

        $sql = 'INSERT INTO notifications(userid, category, send_email, body, is_ack) VALUES(:userid, :category, :send_email, :body, :is_ack)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':category', $this->category->value, PDO::PARAM_INT);
        $req->bindParam(':send_email', $sendEmail, PDO::PARAM_INT);
        $req->bindParam(':body', $jsonBody);
        $req->bindParam(':is_ack', $isAck, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * The "body" of a notification is an array of the required data for that particular notification
     * Some notifications don't need one, some will have several variables. It is stored as JSON.
     */
    protected function getBody(): array
    {
        return array();
    }

    /**
     * @return array<int, int>
     */
    protected function getPref(int $userid): array
    {
        // only categories inferior to 20 have a user setting for email/web notif
        if ($this->category->value >= 20) {
            return array(1, 1);
        }

        $userData = (new Users($userid))->userData;
        return array($userData[$this::PREF], $userData[$this::PREF . '_email']);
    }
}
