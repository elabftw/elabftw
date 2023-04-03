<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use LdapRecord\LdapRecordException;
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
            'username' => 'phpunit',
            'password' => 'phpunitftw',
            'use_tls' => false,
        );
        $connection = new ConnectionFake($ldapConfig, new LdapFake());

        $this->AuthService = new Ldap($connection, new Entry(), $configArr, 'phpunit@example.com', 'phpunitftw');
    }

    public function testTryAuth(): void
    {
        $this->expectException(LdapRecordException::class);
        $this->AuthService->tryAuth();
    }
}
