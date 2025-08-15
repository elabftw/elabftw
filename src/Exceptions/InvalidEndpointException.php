<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Exceptions;

use Elabftw\Enums\ApiEndpoint;

/**
 * For invalid api endpoint
 */
final class InvalidEndpointException extends ImproperActionException
{
    public function __construct()
    {
        parent::__construct(sprintf('Invalid endpoint: available endpoints: %s', implode(', ', ApiEndpoint::getCases())));
    }
}
