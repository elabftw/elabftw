<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\App;
use Elabftw\Elabftw\BuildInfo;
use Elabftw\Enums\Notifications;
use Elabftw\Exceptions\ImproperActionException;

use function sprintf;
use function _;
use function htmlspecialchars;

/**
 * When values need to be transformed before display
 */
final class Transform
{
    /**
     * Create a hidden input element for injecting CSRF token
     */
    public static function csrf(string $token): string
    {
        return sprintf("<input type='hidden' name='csrf' value='%s' />", $token);
    }

    // generate html for a notification to show on web interface
    public static function notif(array $notif): string
    {
        $relativeMoment = '<br><span class="relative-moment" title="%s"></span>';

        return match (Notifications::from($notif['category'])) {
            Notifications::CommentCreated =>
                sprintf(
                    '<span data-action="ack-notif" data-id="%d" data-href="%s?mode=view&amp;id=%d">%s</span>' . $relativeMoment,
                    (int) $notif['id'],
                    $notif['body']['page'],
                    (int) $notif['body']['entity_id'],
                    _('New comment on your entry.'),
                    $notif['created_at'],
                ),
            Notifications::BookingApprovalRequested =>
                sprintf(
                    '<span data-action="ack-notif" data-id="%d" data-href="scheduler.php?tab=3">%s</span>' . $relativeMoment,
                    (int) $notif['id'],
                    sprintf(_('%s requested booking approval.'), htmlspecialchars($notif['body']['requester_fullname'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
                    $notif['created_at'],
                ),
            Notifications::BookingApprovalUpdated =>
                sprintf(
                    '<span data-action="ack-notif" data-id="%d" data-href="scheduler.php?tab=%d">%s</span>' . $relativeMoment,
                    (int) $notif['id'],
                    $notif['body']['status'] === 'cancelled' ? 3 : 2,
                    $notif['body']['status'] === 'cancelled'
                        ? sprintf(
                            _('%s cancelled a pending booking for %s.'),
                            htmlspecialchars($notif['body']['requester_fullname'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            htmlspecialchars($notif['body']['item_title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                        )
                        : sprintf(_('Booking request status: %s'), htmlspecialchars($notif['body']['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
                    $notif['created_at'],
                ),
            Notifications::EventDeleted =>
                sprintf(
                    '<span data-action="ack-notif" data-id="%d" data-href="scheduler.php?items[]=%d">%s (%s)</span>' . $relativeMoment,
                    (int) $notif['id'],
                    (int) $notif['body']['event']['item'],
                    _('A booked slot was deleted from the scheduler.'),
                    $notif['body']['actor'],
                    $notif['created_at'],
                ),
            Notifications::UserCreated =>
                sprintf(
                    '<span data-action="ack-notif" data-id="%d">%s</span>' . $relativeMoment,
                    (int) $notif['id'],
                    _('New user added to your team'),
                    $notif['created_at'],
                ),
            Notifications::UserNeedValidation =>
                sprintf(
                    '<span data-action="ack-notif" data-id="%d" data-href="admin.php">%s</span>' . $relativeMoment,
                    (int) $notif['id'],
                    _('A user needs account validation.'),
                    $notif['created_at'],
                ),
            Notifications::PdfGenericError =>
                sprintf(
                    '<span data-action="ack-notif" data-id="%d">%s</span>' . $relativeMoment,
                    (int) $notif['id'],
                    _('There was a problem during PDF creation.'),
                    $notif['created_at'],
                ),
            Notifications::MathjaxFailed =>
                sprintf(
                    '<span data-action="ack-notif" data-id="%d" data-href="%s?mode=view&amp;id=%d">%s</span>' . $relativeMoment,
                    (int) $notif['id'],
                    $notif['body']['entity_page'],
                    (int) $notif['body']['entity_id'],
                    _('Tex rendering failed during PDF generation. The raw tex commands are retained but you might want to carefully check the generated PDF.'),
                    $notif['created_at'],
                ),
            Notifications::PdfAppendmentFailed =>
                sprintf(
                    '<span data-action="ack-notif" data-id="%d" data-href="%s?mode=view&amp;id=%d">%s (%s)</span>' . $relativeMoment,
                    (int) $notif['id'],
                    $notif['body']['entity_page'],
                    (int) $notif['body']['entity_id'],
                    _('Some attached PDFs could not be appended.'),
                    $notif['body']['file_names'],
                    $notif['created_at'],
                ),
            Notifications::StepDeadline =>
                sprintf(
                    '<span data-action="ack-notif" data-id="%d" data-href="%s?mode=view&amp;id=%d&amp;highlightstep=%d#step_view_%d">%s</span>' . $relativeMoment,
                    (int) $notif['id'],
                    $notif['body']['entity_page'],
                    (int) $notif['body']['entity_id'],
                    (int) $notif['body']['step_id'],
                    (int) $notif['body']['step_id'],
                    _('A step deadline is approaching.'),
                    $notif['created_at'],
                ),
            Notifications::NewVersionInstalled =>
                sprintf(
                    '<a class="text-white" href="%s" target="_blank">%s</a>' . $relativeMoment,
                    App::getWhatsnewLink(BuildInfo::VERSION_INT),
                    sprintf(_('A new eLabFTW version has been installed since your last visit.%sRead the release notes by clicking this message.'), '<br>'),
                    $notif['created_at'],
                ),
            Notifications::ActionRequested =>
                sprintf(
                    '<span data-action="ack-notif" data-id="%d" data-href="%s?mode=view&amp;id=%d">%s</span>' . $relativeMoment,
                    (int) $notif['id'],
                    $notif['body']['entity_page'],
                    (int) $notif['body']['entity_id'],
                    sprintf(
                        _('%s has requested %s from you.'),
                        $notif['body']['requester_fullname'],
                        $notif['body']['action'],
                    ),
                    $notif['created_at'],
                ),
            Notifications::WebhookDisabled =>
                sprintf(
                    '<span data-action="ack-notif" data-id="%d">%s</span>' . $relativeMoment,
                    (int) $notif['id'],
                    sprintf(
                        _('A webhook was disabled after too many failed deliveries: %s'),
                        htmlspecialchars($notif['body']['url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    ),
                    $notif['created_at'],
                ),
            default => throw new ImproperActionException('Invalid notification type.'),
        };
    }
}
