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

use Elabftw\Elabftw\Tools;
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
}
