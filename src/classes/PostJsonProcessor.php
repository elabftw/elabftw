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
 * Get a JSON payload sent by POST
 */
class PostJsonProcessor extends AbstractJsonProcessor
{
    protected function getJson(Request $request): string
    {
        return (string) $request->getContent();
    }
}
