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
use function str_pad;

class MfaHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerateSecret(): void
    {
        // no secret provided, it'll generate one
        $secret = new MfaHelper()->secret;
        $this->assertSame(32, strlen($secret));
        // with a secret provided, it must not change
        $helper = new MfaHelper($secret);
        $this->assertSame($secret, $helper->secret);
    }

    public function testVerifyCode(): void
    {
        $helper = new MfaHelper();
        $goodCode = $helper->getCode();
        $badCode = str_pad((string) (((int) $goodCode + 1) % 1000000), 6, '0', STR_PAD_LEFT);
        $this->assertTrue($helper->verifyCode($goodCode));
        $this->assertFalse($helper->verifyCode($badCode));
    }
}
