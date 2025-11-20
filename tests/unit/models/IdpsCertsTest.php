<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Services\Filter;
use Elabftw\Traits\TestsUtilsTrait;

class IdpsCertsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Users $requester;

    private IdpsCerts $IdpsCerts;

    protected function setUp(): void
    {
        $this->requester = new Users(1, 1);
        $Idps = new Idps($this->requester);
        $params = array(
            'name' => 'testidp',
            'entityid' => 'https://app.onelogin.com/',
            'sso_url' => 'https://onelogin.com/',
            'sso_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'slo_url' => 'https://onelogin.com/',
            'slo_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'email_attr' => 'User.email',
            'team_attr' => 'User.team',
            'fname_attr' => 'User.FirstName',
            'lname_attr' => 'User.LastName',
        );
        $id = $Idps->postAction(Action::Create, $params);
        $this->IdpsCerts = new IdpsCerts($this->requester, $id);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals(sprintf('api/v2/idps/%d/certs/0', $this->IdpsCerts->idpId), $this->IdpsCerts->getApiPath());
    }

    public function testNotSysadmin(): void
    {
        $IdpsCerts = new IdpsCerts($this->getUserInTeam(1));
        $this->expectException(IllegalActionException::class);
        $IdpsCerts->postAction(Action::Create, array());
    }

    public function testCreateReadDestroy(): void
    {
        $cert = Storage::FIXTURES->getStorage()->getFs()->read('x509.crt');
        $id = $this->IdpsCerts->postAction(Action::Create, array('x509' => $cert));
        // a second time to touch other branch
        $id2 = $this->IdpsCerts->postAction(Action::Create, array('x509' => $cert));
        $this->assertSame($id, $id2);
        $this->assertCount(1, $this->IdpsCerts->readAll());
        $IdpsCert = new IdpsCerts($this->requester, $this->IdpsCerts->idpId, $id);
        $idp = $IdpsCert->readOne();
        $this->assertSame(Filter::pem($cert), $idp['x509']);
        $this->assertTrue($IdpsCert->destroy());
    }

    public function testIncorrectPost(): void
    {
        $this->expectException(ImproperActionException::class);
        new IdpsCerts($this->requester)->postAction(Action::Create, array());
    }
}
