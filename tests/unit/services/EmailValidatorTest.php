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
use Elabftw\Traits\TestsUtilsTrait;

class EmailValidatorTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testValidEmail(): void
    {
        $EmailValidator = new EmailValidator('blah@example.com');
        $EmailValidator->validate();
    }

    public function testDomainIsEmptyString(): void
    {
        $email = 'blah@example.com';
        $EmailValidator = new EmailValidator($email, emailDomain: '');
        $this->assertEquals($email, $EmailValidator->validate());
    }

    public function testAdminsImportUsers(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $email = $user->userData['email'];
        $EmailValidator = new EmailValidator($email, adminsImportUsers: true);
        $this->expectException(ImproperActionException::class);
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
        $EmailValidator = new EmailValidator('tata@yopmail.com');
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

    public function testSkipValidation(): void
    {
        $email = 'a@newdomain.com';
        $EmailValidator = new EmailValidator($email, false, 'yopmail.com', skipDomainValidation: true);
        $this->assertEquals($email, $EmailValidator->validate());
    }
}
