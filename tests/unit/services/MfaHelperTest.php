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
    /** @var string $secret The 2FA test secret */
    private const SECRET = 'EXAMPLE2FASECRET234567ABCDEFGHIJ';

    private MfaHelper $MfaHelper;

    protected function setUp(): void
    {
        $this->MfaHelper = new MfaHelper(1, self::SECRET);
    }

    public function testGenerateSecret()
    {
        $secret = $this->MfaHelper->generateSecret();
        $this->assertEquals(strlen($secret), 32);
        $this->MfaHelper->secret = $secret;
    }

    public function testSaveSecret()
    {
        $this->MfaHelper->saveSecret();
    }

    public function testRemoveSecret()
    {
        $this->MfaHelper->removeSecret();
    }

    public function testVerifyCode()
    {
        $code = $this->MfaHelper->getCode();
        $this->assertTrue($this->MfaHelper->verifyCode($code));
    }
}
