<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

class AuthenticatedUserTest extends \PHPUnit\Framework\TestCase
{
    public function testAuthenticatedUser(): void
    {
        $User = new AuthenticatedUser(1, 1);
        $this->assertInstanceOf(AuthenticatedUser::class, $User);
        $this->assertEquals(1, $User->userData['team']);
        $this->assertEquals('en_GB', $User->userData['lang']);
    }
}
