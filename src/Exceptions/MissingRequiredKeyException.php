<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha <Deltablot>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Exceptions;

use function implode;
use function sprintf;

final class MissingRequiredKeyException extends ImproperActionException
{
    public function __construct(array $missingKeys, array $validKeys)
    {
        parent::__construct(sprintf(
            'Missing required parameter(s): %s. Available parameters are: %s.',
            implode(', ', $missingKeys),
            implode(', ', $validKeys),
        ));
    }
}
