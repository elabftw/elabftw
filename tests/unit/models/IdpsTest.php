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

use Elabftw\Enums\Action;

class IdpsTest extends \PHPUnit\Framework\TestCase
{
    private Idps $Idps;

    protected function setUp(): void
    {
        $this->Idps = new Idps();
    }

    public function testGetPage(): void
    {
        $this->assertEquals('api/v2/idps/', $this->Idps->getPage());
    }

    public function testCreate(): void
    {
        $params = array(
            'name' => 'testidp',
            'entityid' => 'https://app.onelogin.com/',
            'sso_url' => 'https://onelogin.com/',
            'sso_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'slo_url' => 'https://onelogin.com/',
            'slo_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'x509' => 'yep',
            'x509_new' => '',
            'email_attr' => 'User.email',
            'team_attr' => 'User.team',
            'fname_attr' => 'User.FirstName',
            'lname_attr' => 'User.LastName',
        );
        $id = $this->Idps->postAction(Action::Create, $params);
        $this->assertIsInt($id);
        $this->Idps->setId($id);
        $newValue = 'new idp name';
        $response = $this->Idps->patch(Action::Update, array('name' => $newValue));
        $this->assertEquals($newValue, $response['name']);
    }

    public function testReadAll(): void
    {
        $this->assertIsArray($this->Idps->readAll());
    }

    public function testGetActiveByEntityId(): void
    {
        $this->assertIsArray($this->Idps->getEnabledByEntityId('https://app.onelogin.com/'));
    }

    public function testDestroy(): void
    {
        $this->assertTrue($this->Idps->destroy());
    }
}
