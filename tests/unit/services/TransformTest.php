<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

class TransformTest extends \PHPUnit\Framework\TestCase
{
    public function testPermission(): void
    {
        $this->assertEquals('Public', Transform::permission('public'));
        $this->assertEquals('Organization', Transform::permission('organization'));
        $this->assertEquals('Team', Transform::permission('team'));
        $this->assertEquals('Owner + Admin(s)', Transform::permission('user'));
        $this->assertEquals('Owner only', Transform::permission('useronly'));
        $this->assertEquals('An error occurred!', Transform::permission('user2'));
    }

    public function testCsrf(): void
    {
        $token = 'fake-token';
        $input = Transform::csrf($token);
        $this->assertEquals("<input type='hidden' name='csrf' value='$token' />", $input);
    }
}
