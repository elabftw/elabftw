<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

/**
 * A class holding shortcuts to the json permissions
 */
final class PermissionsDefaults
{
    public const FULL = '{"base": 50, "teams": [], "teamgroups": [], "users": []}';

    public const ORGANIZATION = '{"base": 40, "teams": [], "teamgroups": [], "users": []}';

    public const MY_TEAMS = '{"base": 30, "teams": [], "teamgroups": [], "users": []}';

    public const USER = '{"base": 20, "teams": [], "teamgroups": [], "users": []}';

    public const USERONLY = '{"base": 10, "teams": [], "teamgroups": [], "users": []}';
}
