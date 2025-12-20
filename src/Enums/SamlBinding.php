<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

/**
 * Only the SAML bindings we support
 */
enum SamlBinding: int
{
    case HttpPost = 0;
    case HttpRedirect = 1;

    public function toUrn(): string
    {
        return match ($this) {
            self::HttpPost => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            self::HttpRedirect => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        };
    }

    public static function fromUrn(string $urn): self | null
    {
        return match ($urn) {
            'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST' => self::HttpPost,
            'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect' => self::HttpRedirect,
            default => null,
        };
    }
}
