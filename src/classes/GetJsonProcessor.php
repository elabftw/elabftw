<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Symfony\Component\HttpFoundation\Request;

/**
 * Process a JSON payload sent by GET in the "p" parameter
 */
class GetJsonProcessor extends AbstractJsonProcessor
{
    protected function getJson(Request $request): string
    {
        return (string) $request->query->get('p');
    }
}
