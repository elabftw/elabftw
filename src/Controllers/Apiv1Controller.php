<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Override;

/**
 * For API v1 requests (removed)
 */
final class Apiv1Controller extends AbstractApiController
{
    #[Override]
    public function getResponse(): Response
    {
        return new JsonResponse(array('result' => 'error', 'message' => 'API v1 has been removed. Use API v2.'), Response::HTTP_BAD_REQUEST);
    }
}
