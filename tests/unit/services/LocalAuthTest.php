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
use Elabftw\Exceptions\QuantumException;

class LocalAuthTest extends \PHPUnit\Framework\TestCase
{
    private LocalAuth $AuthService;

    protected function setUp(): void
    {
        $this->AuthService = new LocalAuth('phpunit@example.com', 'phpunitftw');
    }

    public function testEmptyPassword(): void
    {
        $this->expectException(QuantumException::class);
        $AuthService = new LocalAuth('phpunit@example.com', '');
    }

    public function testTryAuth(): void
    {
        $authResponse = $this->AuthService->tryAuth();
        $this->assertEquals(1, $authResponse->userid);
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    public function testTryAuthWithInvalidEmail(): void
    {
        $this->expectException(QuantumException::class);
        $AuthService = new LocalAuth('invalid@example.com', 'nopenope');
    }

    public function testTryAuthWithInvalidPassword(): void
    {
        $AuthService = new LocalAuth('phpunit@example.com', 'nopenope');
        $this->expectException(InvalidCredentialsException::class);
        $AuthService->tryAuth();
    }
}
