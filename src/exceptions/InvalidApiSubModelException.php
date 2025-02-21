<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Exceptions;

use Elabftw\Enums\ApiEndpoint;
use Elabftw\Enums\ApiSubModels;

use function implode;
use function sprintf;

/**
 * For invalid api sub models
 */
final class InvalidApiSubModelException extends ImproperActionException
{
    public function __construct(ApiEndpoint $apiEndpoint)
    {
        parent::__construct(sprintf(
            'Incorrect submodel for %s: available models are: %s.',
            $apiEndpoint->value,
            implode(', ', ApiSubModels::validSubModelsForEndpoint($apiEndpoint)),
        ));
    }
}
