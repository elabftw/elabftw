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

use Elabftw\Services\MfaHelper;
use RuntimeException;

class MfaTest extends \PHPUnit\Framework\TestCase
{
    public function testTryAuthWithInvalidCode(): void
    {
        $MfaHelper = new MfaHelper(1);
        $AuthService = new Mfa($MfaHelper, '12');
        $this->expectException(RuntimeException::class);
        $AuthService->tryAuth();
    }

    public function testTryAuthWithValidCode(): void
    {
        $secret = (new MfaHelper(1))->generateSecret();
        $MfaHelper = new MfaHelper(1, $secret);
        $code = $MfaHelper->getCode();
        $AuthService = new Mfa($MfaHelper, $code);
        $authResponse = $AuthService->tryAuth();
        $this->assertTrue($authResponse->hasVerifiedMfa);
        $this->assertEquals(1, $authResponse->userid);
        $this->assertEquals(1, $authResponse->selectedTeam);
    }
}
