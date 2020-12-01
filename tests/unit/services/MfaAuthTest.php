<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Exceptions\InvalidCredentialsException;

class MfaAuthTest extends \PHPUnit\Framework\TestCase
{
    public function testTryAuthWithInvalidCode()
    {
        $MfaHelper = new MfaHelper(1);
        $AuthService = new MfaAuth($MfaHelper, '12');
        $this->expectException(InvalidCredentialsException::class);
        $authResponse = $AuthService->tryAuth();
    }

    public function testTryAuthWithValidCode()
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
