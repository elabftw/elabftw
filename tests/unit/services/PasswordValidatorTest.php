<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Enums\PasswordComplexity;
use Elabftw\Exceptions\ImproperActionException;

class PasswordValidatorTest extends \PHPUnit\Framework\TestCase
{
    private PasswordValidator $PasswordValidator;

    protected function setUp(): void
    {
        $this->PasswordValidator = new PasswordValidator(6, PasswordComplexity::None);

    }

    public function testPasswordLength(): void
    {
        // 12 chars ascii
        $this->assertTrue($this->PasswordValidator->validate('abcdefghijkl'));
        $this->expectException(ImproperActionException::class);
        $this->PasswordValidator->validate('aa');
    }

    public function testPasswordJapanese(): void
    {
        // 12 chars japanese
        $this->assertTrue($this->PasswordValidator->validate('みうろねかたへゆのけをけ'));

        // 5 chars japanese
        $this->expectException(ImproperActionException::class);
        $this->PasswordValidator->validate('みうろねか');
    }

    public function testPasswordWeak(): void
    {
        $PasswordValidator = new PasswordValidator(6, PasswordComplexity::Weak);
        $this->assertTrue($PasswordValidator->validate('Abcdef'));
        // no capital letters but japanese characters
        $this->assertTrue($PasswordValidator->validate('みうろねのけをけか'));
        $this->expectException(ImproperActionException::class);
        $PasswordValidator->validate('abcdefghijkl');
    }

    public function testPasswordMedium(): void
    {
        $PasswordValidator = new PasswordValidator(6, PasswordComplexity::Medium);
        $this->assertTrue($PasswordValidator->validate('Abcdefghijkl6'));
        $this->expectException(ImproperActionException::class);
        $PasswordValidator->validate('Abcdefghijkl');
    }

    public function testPasswordStrong(): void
    {
        $PasswordValidator = new PasswordValidator(6, PasswordComplexity::Strong);
        $this->assertTrue($PasswordValidator->validate('Abcdefghijkl6.'));
        $this->expectException(ImproperActionException::class);
        $PasswordValidator->validate('Abcdefghijkl6');
    }
}
