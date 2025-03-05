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

/**
 * Calculate fingerprint from a compound
 */
final class Fingerprinter
{
    private const string FINGERPRINTER_URL = '/fingerprinter';

    // idea: second argument is Compound
    public function __construct(private HttpGetter $httpGetter, bool $isEnabled)
    {
        if (!$isEnabled) {
            throw new ImproperActionException('Fingerprinting service is not enabled! Please refer to the documentation to enable it.');
        }
    }

    public function calculate(string $fmt, string $data): array
    {
        $res = $this->httpGetter->postJson('https://127.1' . self::FINGERPRINTER_URL, array('fmt' => $fmt, 'data' => $data));
        return json_decode($res, true, 42);
    }
}
