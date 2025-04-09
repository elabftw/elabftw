<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Exceptions\ImproperActionException;
use Override;

/**
 * A user that is not archived
 */
final class ActiveUser extends AuthenticatedUser
{
    #[Override]
    protected function readOneFull(): array
    {
        parent::readOneFull();
        if ($this->userData['archived'] === 1) {
            throw new ImproperActionException('This account is archived and cannot be used as an active account.');
        }
        return $this->userData;
    }
}
