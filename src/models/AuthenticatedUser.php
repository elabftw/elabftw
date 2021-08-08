<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

/**
 * An authenticated user
 */
final class AuthenticatedUser extends ExistingUser
{
    public function __construct(public int $userid, public int $team)
    {
        parent::__construct($userid, $team);
    }
}
