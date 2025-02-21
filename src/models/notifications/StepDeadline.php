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
use Override;

final class StepDeadline extends AbstractNotifications implements MailableInterface
{
    /**
     * Time in minutes before the deadline to send/show notifications
     */
    public const int NOTIFLEADTIME = 30;

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

    #[Override]
    public function create(int $userid): int
    {
        // try to delete already existing notification for this step and return if there was one
        if ($this->destroy()) {
            return 0;
        }

        // otherwise, create a notification
        return parent::create($userid);
    }

    #[Override]
    public function getEmail(): array
    {
        $body = sprintf(
            '%s%s/%s?mode=view&id=%d&highlightstep=%d#step_view_%d',
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

    public function destroy(): bool
    {
        // need to distinguish between items and experiments based on entity_page
        $sql = 'DELETE FROM notifications
            WHERE category = :category
                AND body->"$.step_id" = :step_id
                AND body->"$.entity_page" = :entity_page';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':category', $this->category->value, PDO::PARAM_INT);
        $req->bindValue(':step_id', $this->stepId, PDO::PARAM_INT);
        $req->bindValue(':entity_page', $this->entityPage);
        $this->Db->execute($req);
        return (bool) $req->rowCount();
    }

    #[Override]
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
