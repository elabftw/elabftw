<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Override;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * For API V3 requests
 */
final class Apiv3Controller extends AbstractApiController
{
    #[Override]
    public function getResponse(): Response
    {
        $error = array(
            'code' => 400,
            'message' => 'Not implemented',
            'description' => 'API v3 is not implemented yet!',
        );
        return new JsonResponse($error, $error['code']);
    }
}
