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

class LocalAuthTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->AuthService = new LocalAuth('phpunit@example.com', 'phpunitftw');
    }

    public function testTryAuth()
    {
        $authResponse = $this->AuthService->tryAuth();
        $this->assertEquals('local', $authResponse->isAuthBy);
        $this->assertEquals(1, $authResponse->userid);
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    public function testTryAuthWithInvalidEmail()
    {
        $this->expectException(InvalidCredentialsException::class);
        $AuthService = new LocalAuth('invalid@example.com', 'nopenope');
    }

    public function testTryAuthWithInvalidPassword()
    {
        $AuthService = new LocalAuth('phpunit@example.com', 'nopenope');
        $this->expectException(InvalidCredentialsException::class);
        $authResponse = $AuthService->tryAuth();
    }
}
