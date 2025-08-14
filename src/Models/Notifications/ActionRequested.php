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

use Elabftw\Elabftw\Env;
use Elabftw\Enums\Notifications;
use Elabftw\Enums\RequestableAction;
use Elabftw\Interfaces\MailableInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Users\Users;
use Override;

final class ActionRequested extends AbstractNotifications implements MailableInterface
{
    protected const PREF = 'notif_action_requested';

    protected Notifications $category = Notifications::ActionRequested;

    public function __construct(private Users $requester, private RequestableAction $action, private AbstractEntity $entity)
    {
        parent::__construct();
    }

    #[Override]
    public function getEmail(): array
    {
        // body is split in two so we can reuse the translated string in web notification
        $body = sprintf(
            _('%s has requested %s from you.'),
            $this->requester->userData['fullname'],
            $this->action->toHuman(),
        );
        $body .= ' ' . sprintf(
            _('You can review the request here: %s'),
            sprintf('%s/%s?mode=view&id=%d', Env::asUrl('SITE_URL'), $this->entity->entityType->toPage(), $this->entity->id ?? 0)
        );
        return array(
            'subject' => _('Action requested'),
            'body' => $body,
            'replyTo' => $this->requester->userData['email'],
        );
    }

    #[Override]
    protected function getBody(): array
    {
        return array(
            'requester_fullname' => $this->requester->userData['fullname'],
            'requester_userid' => $this->requester->userid,
            'action' => $this->action->toHuman(),
            'action_enum_value' => $this->action->value,
            'entity_page' => $this->entity->entityType->toPage(),
            'entity_id' => $this->entity->id,
            'entity_type_value' => $this->entity->entityType->value,
        );
    }
}
