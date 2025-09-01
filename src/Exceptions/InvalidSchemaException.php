<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Exceptions;

use Exception;

use function dirname;
use function file_get_contents;

/**
 * When the database schema is wrong.
 */
final class InvalidSchemaException extends Exception
{
    /**
     * The message will always be the same here
     */
    public function __construct()
    {
        $htmlPage = file_get_contents(dirname(__DIR__) . '/templates/invalid-schema.html');
        if ($htmlPage === false) {
            $htmlPage = 'Run the bin/console db:update command to finish the update!';
        }
        parent::__construct($htmlPage);
    }
}
