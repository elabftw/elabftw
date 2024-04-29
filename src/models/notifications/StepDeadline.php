<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Notifications;

use Elabftw\Enums\Notifications;
use Elabftw\Interfaces\MailableInterface;
use Elabftw\Models\Config;
use PDO;

class StepDeadline extends AbstractNotifications implements MailableInterface
{
    protected const PREF = 'notif_step_deadline';

    protected Notifications $category = Notifications::StepDeadline;

    public function __construct(
        private int $stepId,
        private int $entityId,
        private string $entityPage,
        private string $deadline,
    ) {
        parent::__construct();
    }

    /**
     * For step notification, we first need to check if there isn't one already existing before creating a new one for this step
     */
    public function create(int $userid): int
    {
        // check if a similar notification is not already there
        $sql = 'SELECT id FROM notifications WHERE category = :category AND JSON_EXTRACT(body, "$.step_id") = :step_id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':category', $this->category->value, PDO::PARAM_INT);
        $req->bindValue(':step_id', $this->stepId, PDO::PARAM_INT);
        $this->Db->execute($req);
        // if there is a notification for this step id, delete it
        if ($req->rowCount() > 0) {
            $sql = 'DELETE FROM notifications WHERE id = :id';
            $reqDel = $this->Db->prepare($sql);
            $reqDel->bindValue(':id', $req->fetch()['id'], PDO::PARAM_INT);
            $reqDel->execute();
            return 0;
        }
        // otherwise, create a notification for it
        return parent::create($userid);
    }

    public function getEmail(): array
    {
        $body = sprintf(
            '%s%s/%s.php?mode=view&id=%d&highlightstep=%d#step_view_%d',
            _('Hello. A step deadline is approaching: '),
            Config::fromEnv('SITE_URL'),
            $this->entityPage,
            $this->entityId,
            $this->stepId,
            $this->stepId,
        );

        return array(
            'subject' => _('A step deadline is approaching.'),
            'body' => $body,
        );
    }

    protected function getBody(): array
    {
        return array(
            'step_id' => $this->stepId,
            'entity_id' => $this->entityId,
            'entity_page' => $this->entityPage,
            'deadline' => $this->deadline,
        );
    }
}
