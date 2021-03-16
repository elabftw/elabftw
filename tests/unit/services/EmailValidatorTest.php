<?php declare(strict_types=1);
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
    public function testValidEmail()
    {
        $EmailValidator = new EmailValidator('blah@example.com');
        $EmailValidator->validate();
    }

    public function testInvalidEmail()
    {
        $EmailValidator = new EmailValidator('blahexample.com');
        $this->expectException(ImproperActionException::class);
        $EmailValidator->validate();
    }

    public function testDuplicateEmail()
    {
        $EmailValidator = new EmailValidator('phpunit@example.com');
        $this->expectException(ImproperActionException::class);
        $EmailValidator->validate();
    }

    public function testForbiddenDomain()
    {
        $EmailValidator = new EmailValidator('yolololol@yopmail.com', 'example.org');
        $this->expectException(ImproperActionException::class);
        $EmailValidator->validate();
    }

    public function testAllowedDomain()
    {
        $EmailValidator = new EmailValidator('yolololol@yopmail.com', 'yopmail.com');
        $EmailValidator->validate();
    }
}
