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

use Elabftw\Elabftw\IdpsHelper;
use Elabftw\Enums\Action;
use Elabftw\Models\Users\Users;

class IdpsTest extends \PHPUnit\Framework\TestCase
{
    private Idps $Idps;

    protected function setUp(): void
    {
        $this->Idps = new Idps(new Users(1, 1));
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/idps/', $this->Idps->getApiPath());
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
        $helper = new IdpsHelper(Config::getConfig(), $this->Idps);
        $settings = $helper->getSettings($id);
        // check orgid requested attribute is not empty
        $this->assertEquals('urn:oid:0.9.2342.19200300.100.1.1', $settings['sp']['attributeConsumingService']['requestedAttributes'][4]['name']);
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->Idps->readAll());
        $this->assertIsArray($this->Idps->readAllSimpleEnabled());
        $this->assertIsArray($this->Idps->readAllLight());
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
