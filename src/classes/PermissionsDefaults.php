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
    // public being a reserved keyword, use the cooler way to write it
    public const PUBLIK = '{"public": true, "organization": false, "my_teams": false, "user": false, "useronly": false, "teams": [], "teamgroups": [], "users": []}';

    public const ORGANIZATION = '{"public": false, "organization": true, "my_teams": false, "user": false, "useronly": false, "teams": [], "teamgroups": [], "users": []}';

    public const MY_TEAMS = '{"public": false, "organization": false, "my_teams": true, "user": false, "useronly": false, "teams": [], "teamgroups": [], "users": []}';

    public const USER = '{"public": false, "organization": false, "my_teams": false, "user": true, "useronly": false, "teams": [], "teamgroups": [], "users": []}';

    public const USERONLY = '{"public": false, "organization": false, "my_teams": false, "user": false, "useronly": true, "teams": [], "teamgroups": [], "users": []}';
}
