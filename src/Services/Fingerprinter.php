<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\FingerprinterInterface;
use JsonException;
use Override;

/**
 * Use an external fingerprinting service to calculate compounds fingerprints
 */
final class Fingerprinter implements FingerprinterInterface
{
    public function __construct(private HttpGetter $httpGetter, private string $url)
    {
        if (trim($this->url) === '') {
            throw new ImproperActionException('Fingerprinting service url is empty − set FINGERPRINTER_URL in your environment configuration');
        }
    }

    #[Override]
    public function calculate(string $fmt, string $data): array
    {
        $response = $this->httpGetter->postJson($this->url, array('fmt' => $fmt, 'data' => $data));
        try {
            return json_decode($response, true, 42, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ImproperActionException('Invalid JSON from fingerprinting service', 0, $e);
        }
    }
}
