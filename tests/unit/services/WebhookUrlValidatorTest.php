<?php

declare(strict_types=1);

/**
 * @author Moritz IHLER
 * @copyright 2026 Moritz IHLER
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Exceptions\ImproperActionException;

use function sprintf;
use function str_repeat;

class WebhookUrlValidatorTest extends \PHPUnit\Framework\TestCase
{
    private WebhookUrlValidator $strict;

    private WebhookUrlValidator $relaxed;

    protected function setUp(): void
    {
        $this->strict = new WebhookUrlValidator(true);
        $this->relaxed = new WebhookUrlValidator(false);
    }

    public function testPublicAddressIsAccepted(): void
    {
        // an address literal so the test does not depend on dns
        $url = 'https://93.184.216.34/hook';
        $this->assertEquals($url, $this->strict->validate($url));
    }

    public function testHttpIsRefused(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->strict->validate('http://93.184.216.34/hook');
    }

    public function testCredentialsAreRefused(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->strict->validate('https://user:pass@93.184.216.34/hook');
    }

    public function testFragmentIsRefused(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->strict->validate('https://93.184.216.34/hook#nope');
    }

    public function testEmptyIsRefused(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->strict->validate('');
    }

    public function testTooLongIsRefused(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->strict->validate('https://93.184.216.34/' . str_repeat('a', WebhookUrlValidator::MAX_URL_LENGTH));
    }

    public function testGarbageIsRefused(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->strict->validate('not a url at all');
    }

    /**
     * These are the addresses that make an admin supplied url an ssrf primitive.
     */
    public function testReservedAddressesAreRefused(): void
    {
        $blocked = array(
            'https://127.0.0.1/hook',
            'https://10.1.2.3/hook',
            'https://172.16.0.1/hook',
            'https://192.168.1.1/hook',
            'https://169.254.169.254/hook',
            'https://100.64.0.1/hook',
            'https://0.0.0.0/hook',
            'https://[::1]/hook',
            'https://[fe80::1]/hook',
            'https://[fc00::1]/hook',
        );
        foreach ($blocked as $url) {
            try {
                $this->strict->validate($url);
                $this->fail(sprintf('%s should have been refused', $url));
            } catch (ImproperActionException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * A development instance talks to a receiver on a private address by definition, exactly
     * like it skips tls verification.
     */
    public function testDevModeAllowsPrivateTargets(): void
    {
        $url = 'http://127.0.0.1:9099/hook';
        $this->assertEquals($url, $this->relaxed->validate($url));
    }

    public function testAddressLiteralIsReturnedAsIs(): void
    {
        $this->assertEquals(array('93.184.216.34'), $this->strict->resolve('93.184.216.34'));
    }
}
