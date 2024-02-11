<?php declare(strict_types=1);
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
    public function testPasswordLength(): void
    {
        $PasswordValidator = new PasswordValidator(12, PasswordComplexity::None);
        $this->expectException(ImproperActionException::class);
        $PasswordValidator->validate('aa');
        // 11 chars japanese
        $this->expectException(ImproperActionException::class);
        $PasswordValidator->validate('みうろねかたへゆのけを');
        // 12 chars japanese
        $this->assertTrue($PasswordValidator->validate('みうろねかたへゆのけをけ'));
        // 12 chars ascii
        $this->assertTrue($PasswordValidator->validate('abcdefghijkl'));
    }

    public function testPasswordWeak(): void
    {
        $PasswordValidator = new PasswordValidator(6, PasswordComplexity::Weak);
        $this->expectException(ImproperActionException::class);
        $PasswordValidator->validate('abcdefghijkl');
        $this->assertTrue($PasswordValidator->validate('Abcdefghijkl'));
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
