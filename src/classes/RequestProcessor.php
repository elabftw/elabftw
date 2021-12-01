<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Symfony\Component\HttpFoundation\Request;

/**
 * Process a JSON payload send by GET in the "p" parameter
 */
class RequestProcessor extends AbstractProcessor
{
    protected function process(Request $request): void
    {
        $this->processJson((string) $request->query->get('p'));
    }
}
