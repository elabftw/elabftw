<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use InvalidArgumentException;
use Mpdf\Http\ClientInterface;
use Override;
use Psr\Http\Message\RequestInterface;

/**
 * Prevent mPDF from making network requests for user-controlled resources.
 */
final class MpdfRemoteContentBlocker implements ClientInterface
{
    #[Override]
    public function sendRequest(RequestInterface $request): never
    {
        throw new InvalidArgumentException('Remote content fetching is disabled.');
    }
}
