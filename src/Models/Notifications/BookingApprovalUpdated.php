<?php

declare(strict_types=1);

namespace Elabftw\Models\Notifications;

use Elabftw\Elabftw\Env;
use Elabftw\Enums\Notifications;
use Elabftw\Interfaces\MailableInterface;
use Elabftw\Models\Users\Users;
use Override;

use function _;
use function sprintf;

/** Booking submission, approval or rejection update. */
final class BookingApprovalUpdated extends AbstractNotifications implements MailableInterface
{
    protected const PREF = 'notif_booking_approval_updated';

    protected Notifications $category = Notifications::BookingApprovalUpdated;

    public function __construct(Users $targetUser, private array $details)
    {
        parent::__construct($targetUser);
    }

    #[Override]
    public function getEmail(): array
    {
        $status = $this->details['status'];
        $subject = match ($status) {
            'pending' => _('Your booking request is pending approval'),
            'approved' => _('A booking request has been approved'),
            'rejected' => _('A booking request has been rejected'),
            'cancelled' => _('A pending booking request has been cancelled'),
            default => _('Booking request updated'),
        };
        $body = sprintf(
            _('Booking for %s from %s to %s: %s. See: %s'),
            $this->details['item_title'],
            $this->details['start'],
            $this->details['end'],
            $status,
            sprintf('%s/scheduler.php?tab=%d', Env::asUrl('SITE_URL'), $this->details['tab'] ?? ($status === 'cancelled' ? 3 : 2)),
        );
        if (!empty($this->details['comment'])) {
            $body .= sprintf("\n\n%s: %s", _('Comment'), $this->details['comment']);
        }
        return array('subject' => $subject, 'body' => $body);
    }

    #[Override]
    protected function getBody(): array
    {
        return $this->details;
    }
}
