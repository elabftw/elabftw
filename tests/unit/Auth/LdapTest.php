<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use Elabftw\Exceptions\InvalidCredentialsException;
use LdapRecord\Models\Entry;
use LdapRecord\Testing\ConnectionFake;
use LdapRecord\Testing\LdapFake;

class LdapTest extends \PHPUnit\Framework\TestCase
{
    private Ldap $AuthService;

    protected function setUp(): void
    {
        $configArr = array(
            'ldap_base_dn' => 'cn=User,dc=local,dc=com',
            'ldap_email' => 'mail',
            'ldap_search_attr' => 'mail',
            'ldap_firstname' => 'givenname',
            'ldap_lastname' => 'sn',
            'ldap_team' => 'ou',
        );
        $ldapConfig = array(
            'hosts' => array('127.0.0.1'),
            'port' => 389,
            'base_dn' => $configArr['ldap_base_dn'],
            'username' => 'Toto',
            'password' => 'totototototo',
            'use_tls' => false,
        );
        $fake = new LdapFake();
        $fake->expect(array(
            LdapFake::operation('bind')->once()->with($ldapConfig['username'], $ldapConfig['password'])->andReturnResponse(),
            LdapFake::operation('search')->once()->andReturn(array()),
        ));
        $connection = new ConnectionFake($ldapConfig, $fake);

        $this->AuthService = new Ldap($connection, new Entry(), $configArr, 'toto@yopmail.com', 'totototototo');
    }

    public function testTryAuth(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->AuthService->tryAuth();
    }
}
