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

class MfaTest extends \PHPUnit\Framework\TestCase
{
    public function testTryAuthWithInvalidCode(): void
    {
        $MfaHelper = new MfaHelper();
        $AuthService = new Mfa($MfaHelper, 1, '12');
        $this->expectException(InvalidMfaCodeException::class);
        $AuthService->tryAuth();
    }

    public function testTryAuthWithValidCode(): void
    {
        $MfaHelper = new MfaHelper();
        $code = $MfaHelper->getCode();
        $AuthService = new Mfa($MfaHelper, 1, $code);
        $authResponse = $AuthService->tryAuth();
        $this->assertTrue($authResponse->hasVerifiedMfa());
        $this->assertEquals(1, $authResponse->getAuthUserid());
        $this->assertEquals(1, $authResponse->getSelectedTeam());
    }
}
