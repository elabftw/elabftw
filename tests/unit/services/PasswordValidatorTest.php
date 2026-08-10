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
    public function testPasswordLength(): void
    {
        // 12 chars ascii
        $this->assertTrue(new PasswordValidator(6, PasswordComplexity::None, 'abcdefghijkl')->validate());
        $this->expectException(ImproperActionException::class);
        new PasswordValidator(6, PasswordComplexity::None, 'ab')->validate();
    }

    public function testPasswordJapanese(): void
    {
        // 12 chars japanese
        $this->assertTrue(new PasswordValidator(6, PasswordComplexity::None, 'みうろねかたへゆのけをけ')->validate());

        // 5 chars japanese
        $this->expectException(ImproperActionException::class);
        new PasswordValidator(6, PasswordComplexity::None, 'みうろねか')->validate();
    }

    public function testPasswordWeak(): void
    {
        $this->assertTrue(new PasswordValidator(6, PasswordComplexity::Weak, 'Abcdef')->validate());
        // no capital letters but japanese characters
        $this->assertTrue(new PasswordValidator(6, PasswordComplexity::Weak, 'みうろねのけをけか')->validate());
        $this->expectException(ImproperActionException::class);
        new PasswordValidator(6, PasswordComplexity::Weak, 'abcdefghijkl')->validate();
    }

    public function testPasswordMedium(): void
    {
        $this->assertTrue(new PasswordValidator(6, PasswordComplexity::Medium, 'Abcdefghijkl6')->validate());
        $this->expectException(ImproperActionException::class);
        new PasswordValidator(6, PasswordComplexity::Medium, 'Abcdefghijkl')->validate();
    }

    public function testPasswordStrong(): void
    {
        $this->assertTrue(new PasswordValidator(6, PasswordComplexity::Strong, 'Abcdefghijkl6.')->validate());
        $this->expectException(ImproperActionException::class);
        new PasswordValidator(6, PasswordComplexity::Strong, 'Abcdefghijkl6')->validate();
    }
}
