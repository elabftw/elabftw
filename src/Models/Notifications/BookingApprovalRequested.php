<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @author Moustapha / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Notifications;

use Elabftw\Elabftw\Env;
use Elabftw\Enums\Notifications;
use Elabftw\Interfaces\MailableInterface;
use Elabftw\Models\Users\Users;
use Override;

use function _;
use function sprintf;

/** Send a booking approval request to an authorized validator. */
final class BookingApprovalRequested extends AbstractNotifications implements MailableInterface
{
    protected const PREF = 'notif_booking_approval_requested';

    protected Notifications $category = Notifications::BookingApprovalRequested;

    public function __construct(Users $targetUser, private array $details)
    {
        parent::__construct($targetUser);
    }

    #[Override]
    public function getEmail(): array
    {
        return array(
            'subject' => _('A booking is waiting for your approval'),
            'body' => sprintf(
                _('%s requests to book %s from %s to %s (%d occurrence(s)). Review the request in eLabFTW: %s'),
                $this->details['requester_fullname'],
                $this->details['item_title'],
                $this->details['start'],
                $this->details['end'],
                $this->details['occurrences'],
                sprintf('%s/scheduler.php?tab=3', Env::asUrl('SITE_URL')),
            ),
        );
    }

    #[Override]
    protected function getBody(): array
    {
        return $this->details;
    }
}
