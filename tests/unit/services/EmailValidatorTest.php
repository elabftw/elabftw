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

use Elabftw\Exceptions\ImproperActionException;

class EmailValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function testValidEmail(): void
    {
        $EmailValidator = new EmailValidator('blah@example.com');
        $EmailValidator->validate();
    }

    public function testInvalidEmail(): void
    {
        $EmailValidator = new EmailValidator('blahexample.com');
        $this->expectException(ImproperActionException::class);
        $EmailValidator->validate();
    }

    public function testDuplicateEmail(): void
    {
        $EmailValidator = new EmailValidator('tatabis@yopmail.com');
        $this->expectException(ImproperActionException::class);
        $EmailValidator->validate();
    }

    public function testForbiddenDomain(): void
    {
        $EmailValidator = new EmailValidator('yolololol@yopmail.com', false, 'example.org');
        $this->expectException(ImproperActionException::class);
        $EmailValidator->validate();
    }

    public function testAllowedDomain(): void
    {
        $EmailValidator = new EmailValidator('yolololol@yopmail.com', false, 'yopmail.com');
        $EmailValidator->validate();
    }
}
