<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Exceptions;

use function _;

/**
 * A new compound has the identifier of an existing one that the requester cannot read: the identifiers are unique on the instance.
 * Imports skip such a compound instead of the whole row or entry.
 */
final class CompoundNotAccessibleException extends ImproperActionException
{
    public function __construct()
    {
        parent::__construct(_('A compound with the same identifier already exists, but you do not have access to it.'));
    }
}
