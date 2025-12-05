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
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\SamlBinding;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

class IdpsEndpointsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Users $requester;

    private IdpsEndpoints $IdpsEndpoints;

    protected function setUp(): void
    {
        $this->requester = new Users(1, 1);
        $Idps = new Idps($this->requester);
        $params = array(
            'name' => 'testidp',
            'entityid' => 'https://app.onelogin.com/',
            'email_attr' => 'User.email',
            'team_attr' => 'User.team',
            'fname_attr' => 'User.FirstName',
            'lname_attr' => 'User.LastName',
        );
        $id = $Idps->postAction(Action::Create, $params);
        $this->IdpsEndpoints = new IdpsEndpoints($this->requester, $id);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals(sprintf('api/v2/idps/%d/endpoints/0', $this->IdpsEndpoints->idpId), $this->IdpsEndpoints->getApiPath());
    }

    public function testNotSysadmin(): void
    {
        $IdpsCerts = new IdpsEndpoints($this->getUserInTeam(1));
        $this->expectException(IllegalActionException::class);
        $IdpsCerts->postAction(Action::Create, array());
    }

    public function testCreateReadDestroy(): void
    {
        $this->assertCount(0, $this->IdpsEndpoints->readAll());
        $location = 'https://idp.example.com/SSO';
        $id = $this->IdpsEndpoints->postAction(Action::Create, array('location' => $location));
        $this->assertCount(1, $this->IdpsEndpoints->readAll());
        $this->IdpsEndpoints->setId($id);
        $endpoint = $this->IdpsEndpoints->readOne();
        $this->assertSame($endpoint['location'], $location);
        $this->assertTrue($this->IdpsEndpoints->destroy());
    }

    public function testIncorrectPost(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->IdpsEndpoints->postAction(Action::Create, array());
    }

    public function testIncorrectPostNoId(): void
    {
        $this->IdpsEndpoints->idpId = null;
        $this->expectException(ImproperActionException::class);
        $this->IdpsEndpoints->postAction(Action::Create, array());
    }

    public function testSync(): void
    {
        // first create an endpoint for our idp
        $this->IdpsEndpoints->postAction(Action::Create, array('location' => 'https://a.fr'));
        // now we will simulate the XML providing a new endpoint
        $newLocation = 'https://b.fr';
        $idpFromXml = array(
            'name' => 'idp',
            'entityid' => 'https://app.onelogin.com',
            'endpoints' => array(
                array(
                    'binding' => SamlBinding::HttpRedirect,
                    'location' => $newLocation,
                    'is_slo' => BinaryValue::False,
                ),
            ),
        );
        // this should replace the existing cert with the provided one
        $this->IdpsEndpoints->sync($this->IdpsEndpoints->idpId, $idpFromXml);
        $afterSync = $this->IdpsEndpoints->readAll();
        $this->assertCount(1, $afterSync);
        $this->assertSame($newLocation, $afterSync[0]['location']);
    }
}
