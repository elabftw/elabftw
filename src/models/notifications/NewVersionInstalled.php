<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Notifications;

use Elabftw\Enums\Notifications;
use PDO;

class NewVersionInstalled extends AbstractNotifications
{
    protected Notifications $category = Notifications::NewVersionInstalled;

    public function create(int $userid): int
    {
        $sql = 'INSERT INTO notifications(userid, category, send_email, body, is_ack) VALUES(:userid, :category, 0, "{}", 0)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':category', $this->category->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }
}
