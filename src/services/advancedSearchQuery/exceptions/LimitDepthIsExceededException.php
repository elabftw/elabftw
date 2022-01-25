<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services\AdvancedSearchQuery\Exceptions;

use Exception;

class LimitDepthIsExceededException extends Exception
{
    public function __construct()
    {
        $message = _('Query is too complex!');
        parent::__construct($message, 403);
    }
}
