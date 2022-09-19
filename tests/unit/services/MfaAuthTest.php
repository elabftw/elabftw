<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use RuntimeException;

class MfaAuthTest extends \PHPUnit\Framework\TestCase
{
    public function testTryAuthWithInvalidCode(): void
    {
        $MfaHelper = new MfaHelper(1);
        $AuthService = new MfaAuth($MfaHelper, '12');
        $this->expectException(RuntimeException::class);
        $AuthService->tryAuth();
    }

    public function testTryAuthWithValidCode(): void
    {
        $secret = (new MfaHelper(1))->generateSecret();
        $MfaHelper = new MfaHelper(1, $secret);
        $code = $MfaHelper->getCode();
        $AuthService = new MfaAuth($MfaHelper, $code);
        $authResponse = $AuthService->tryAuth();
        $this->assertTrue($authResponse->hasVerifiedMfa);
        $this->assertEquals(1, $authResponse->userid);
        $this->assertEquals(1, $authResponse->selectedTeam);
    }
}
