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
use function sprintf;
use function strtr;

/**
 * When the database schema is wrong.
 */
final class InvalidSchemaException extends Exception
{
    /**
     * The message will always be the same here
     */
    public function __construct(int $currentSchema, int $requiredSchema)
    {
        $htmlPage = file_get_contents(dirname(__DIR__) . '/templates/invalid-schema.html');

        if ($htmlPage === false) {
            $htmlPage = sprintf('Run the bin/console db:update command to finish the update! (%d => %d)', $currentSchema, $requiredSchema);
        }
        $html = strtr($htmlPage, array(
            '%CURRENT_SCHEMA%'  => (string) $currentSchema,
            '%REQUIRED_SCHEMA%' => (string) $requiredSchema,
        ));
        parent::__construct($html);
    }
}
