<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Exceptions;

use Exception;

final class LimitDepthIsExceededException extends Exception
{
    public function __construct()
    {
        $message = _('Query is too complex!');
        parent::__construct($message, 403);
    }
}
