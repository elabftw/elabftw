<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use Elabftw\Exceptions\InvalidMfaCodeException;
use Elabftw\Services\MfaHelper;
use Symfony\Component\HttpFoundation\Session\Session;

class MfaTest extends \PHPUnit\Framework\TestCase
{
    public function testTryAuthWithInvalidCode(): void
    {
        $verifier = new MfaVerifier(new Session());
        $this->expectException(InvalidMfaCodeException::class);
        $verifier->verify(1, '12');
    }

    public function testTryAuthWithValidCode(): void
    {
        $MfaHelper = new MfaHelper();
        $code = $MfaHelper->getCode();
        $session = new Session();
        $session->set('mfa_secret', $MfaHelper->secret);
        $verifier = new MfaVerifier($session);
        $verifier->verify(1, $code);
        $this->assertFalse($session->has('mfa_secret'));
    }
}
