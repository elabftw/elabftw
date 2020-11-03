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

use LdapRecord\Models\ModelNotFoundException;
use LdapRecord\Testing\ConnectionFake;
use LdapRecord\Testing\LdapFake;

class LdapAuthTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $baseDn = 'cn=User,dc=local,dc=com';
        $ldapConfig = array(
            'hosts' => array('127.0.0.1'),
            'port' => 389,
            'base_dn' => $baseDn,
            'username' => 'phpunit',
            'password' => 'phpunitftw',
            'use_tls' => false,
        );
        $connection = new ConnectionFake($ldapConfig, new LdapFake());
        $this->AuthService = new LdapAuth($connection, $baseDn, 'phpunit@example.com', 'phpunitftw');
    }

    public function testTryAuth()
    {
        $this->expectException(ModelNotFoundException::class);
        $authResponse = $this->AuthService->tryAuth();
    }
}
