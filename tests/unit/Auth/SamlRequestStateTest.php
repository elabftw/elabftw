<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use Elabftw\Exceptions\UnauthorizedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class SamlRequestStateTest extends TestCase
{
    public function testReadsRequestStateFromCookies(): void
    {
        $state = new SamlRequestState(new Request(cookies: array(
            'saml_request_id' => '_request-id',
            'saml_request_idp' => '42',
        )));

        self::assertSame('_request-id', $state->getRequestId());
        self::assertSame(42, $state->getIdpId());
    }

    public function testMissingRequestIdIsRejected(): void
    {
        $this->expectException(UnauthorizedException::class);

        new SamlRequestState(new Request())->getRequestId();
    }

    public function testInvalidIdpIdIsRejected(): void
    {
        $state = new SamlRequestState(new Request(cookies: array(
            'saml_request_idp' => '0',
        )));

        $this->expectException(UnauthorizedException::class);

        $state->getIdpId();
    }

    public function testStoreAndClear(): void
    {
        $state = new SamlRequestState(new Request());

        $state->store('_request-id', 42);
        $state->clear();

        self::addToAssertionCount(1);
    }
}
