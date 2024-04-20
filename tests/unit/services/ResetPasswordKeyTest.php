<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users;

use function time;

class ResetPasswordKeyTest extends \PHPUnit\Framework\TestCase
{
    private ResetPasswordKey $ResetPasswordKey;

    private string $secretKey;

    protected function setUp(): void
    {
        $this->secretKey = Key::createNewRandomKey()->saveToAsciiSafeString();
        $this->ResetPasswordKey = new ResetPasswordKey(time(), $this->secretKey);
    }

    public function testValidate(): void
    {
        $key = $this->ResetPasswordKey->generate('toto@yopmail.com');

        $Users = $this->ResetPasswordKey->validate($key);
        $this->assertInstanceOf(Users::class, $Users);
    }

    public function testValidateInvalidKey(): void
    {
        $this->expectException(WrongKeyOrModifiedCiphertextException::class);
        $this->ResetPasswordKey->validate('invalid-key');
    }

    public function testValidateIllegalKey(): void
    {
        $key = $this->ResetPasswordKey->generate('a@a.com@BiB6y21q>,6;,*C;A.b$$BpD"Mal<%1*');
        $this->expectException(IllegalActionException::class);
        $this->ResetPasswordKey->validate($key);
    }

    public function testValidateExpiredKey(): void
    {
        $key = $this->ResetPasswordKey->generate('toto@yopmail.com');

        $ResetPasswordKey = new ResetPasswordKey(time() + 1337, $this->secretKey);
        $this->expectException(ImproperActionException::class);
        $ResetPasswordKey->validate($key);
    }
}
