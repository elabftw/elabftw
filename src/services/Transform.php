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
        switch ($permission) {
            case 'public':
                $res = _('Public');
                break;
            case 'organization':
                $res = _('Organization');
                break;
            case 'team':
                $res = _('Team');
                break;
            case 'user':
                $res = _('Owner + Admin(s)');
                break;
            case 'useronly':
                $res = _('Owner only');
                break;
            default:
                $res = Tools::error();
        }
        return ucfirst($res);
    }
}
