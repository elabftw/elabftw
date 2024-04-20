<?php

declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

use Codeception\Util\HttpCode;

class IdpCest
{
    public function _before(Apiv2Tester $I)
    {
        $I->haveHttpHeader('Authorization', 'apiKey4Test');
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    public function getAllIdp(Apiv2Tester $I)
    {
        $I->wantTo('Get all idp');
        $I->sendGET('/idps');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function createIdp(Apiv2Tester $I)
    {
        $I->wantTo('Create an idp');
        $I->sendPOST('/idps', array(
            'name' => 'apitest',
            'entityid' => 'https://idp.example.com',
            'sso_url' => 'https://idp.example.com/saml/sso',
            'sso_binding' => 'u',
            'slo_url' => 'https://idp.example.com/saml/slo',
            'slo_binding' => 'u',
            'x509' => 'a',
            'x509_new' => 'a',
            'email_attr' => 'mail',
            'team_attr' => '',
            'fname_attr' => 'givenname',
            'lname_attr' => 'sn',
        ));
        $I->seeResponseCodeIs(HttpCode::CREATED);
    }

    public function getIdp(Apiv2Tester $I)
    {
        $I->wantTo('Get an idp');
        $I->sendGET('/idps/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function updateIdp(Apiv2Tester $I)
    {
        $I->wantTo('Update an idp');
        $I->sendPATCH('/idps/1', array(
            'name' => 'apitest updated',
        ));
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
    }

    public function deletedIdp(Apiv2Tester $I)
    {
        $I->wantTo('Delete an idp');
        $I->sendDELETE('/idps/1');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
    }
}
