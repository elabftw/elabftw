<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use function strlen;

class MfaHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerateSecret(): void
    {
        // no secret provided, it'll generate one
        $secret = new MfaHelper()->secret;
        $this->assertEquals(32, strlen($secret));
        // with a secret provided, it must not change
        $MfaHelper = new MfaHelper($secret);
        $this->assertSame($secret, $MfaHelper->secret);
    }

    public function testVerifyCode(): void
    {
        $MfaHelper = new MfaHelper();
        $goodCode = $MfaHelper->getCode();
        $badCode = ((int) $goodCode) - 1;
        $this->assertTrue($MfaHelper->verifyCode($goodCode));
        $this->assertFalse($MfaHelper->verifyCode((string) $badCode));
    }
}
