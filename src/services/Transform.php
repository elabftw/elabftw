<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Notifications;
use function sprintf;
use function ucfirst;

/**
 * When values need to be transformed before display
 */
class Transform
{
    /**
     * Transform the raw permission value into something human readable
     *
     * @param string $permission raw value (public, organization, team, user, useronly)
     * @return string capitalized and translated permission level
     */
    public static function permission(string $permission): string
    {
        $res = match ($permission) {
            'public' => _('Public'),
            'organization' => _('Organization'),
            'team' => _('Team'),
            'user' => _('Owner + Admin(s)'),
            'useronly' => _('Owner only'),
            default => Tools::error(),
        };
        return ucfirst($res);
    }

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
        // new comment
        if ($notif['category'] === '1') {
            return sprintf(
                '<span class="clickable" data-action="ack-notif" data-id="%d" data-href="experiments.php?mode=view&id=%d">%s</span>',
                (int) $notif['id'],
                (int) $notif['body']['experiment_id'],
                _('New comment on your experiment.'),
            );
        }
        return '';
    }

    public static function notif2Email(array $notif): array
    {
        $subject = '[eLabFTW] ';
        switch ((int) $notif['category']) {
            case Notifications::COMMENT_CREATED:
                $subject .= _('New comment posted');
                $commenter = 'TODO';
                $url = 'TODO';

                $body = sprintf(
                    _('Hi. %s left a comment on your experiment. Have a look: %s'),
                    $commenter,
                    $url,
                );
                break;
            case Notifications::USER_CREATED:
                $subject .= _('New user added to your team');
                $body = sprintf(
                    _('Hi. A new user registered an account on eLabFTW: %s (%s).'),
                    // TODO FIXME
                    'TODO new user name',
                    'TODO new user email',
                );
                break;
            case Notifications::USER_NEED_VALIDATION:
                $subject .= _('[ACTION REQUIRED]') . ' ' . _('New user added to your team');
                $base = sprintf(
                    // TODO copy paste from above
                    _('Hi. A new user registered an account on eLabFTW: %s (%s).'),
                    // TODO FIXME
                    'TODO new user name',
                    'TODO new user email',
                );
                $body =  $base . sprintf(_('Head to the admin panel to validate the account: %s'), 'TODO URL to click to admin panel');
                break;
            default:
                throw new ImproperActionException('Invalid notification category');
        }
        return array('subject' => $subject, 'body' => $body);
    }
}
