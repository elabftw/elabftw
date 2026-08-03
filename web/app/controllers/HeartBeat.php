<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

use function dirname;
use function time;

/**
 * Make sure that the user is still logged in
 */
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

$Session = new Session();
$Session->start();

if (!$Session->get('is_auth')) {
    new JsonResponse(
        null,
        Response::HTTP_UNAUTHORIZED,
    )->send();
    exit;
}

$expiresAt = (int) ($Session->get('session_expires_at') ?? 0);

if ($expiresAt !== 0 && $expiresAt <= time()) {
    $Session->invalidate();

    new JsonResponse(
        null,
        Response::HTTP_UNAUTHORIZED,
    )->send();
    exit;
}

new JsonResponse(array(
    'expires_at' => $expiresAt ?: null,
))->send();
