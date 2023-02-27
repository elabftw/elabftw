<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models\Notifications;

use Elabftw\Enums\Notifications;
use Elabftw\Interfaces\MailableInterface;
use Elabftw\Models\Config;

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
            'subject' => _('A step deadline is approaching'),
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
