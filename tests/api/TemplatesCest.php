<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

use \Codeception\Util\HttpCode;

class TemplatesCest
{
    public function _before(ApiTester $I)
    {
        $I->haveHttpHeader('Authorization', 'apiKey4Test');
    }

    public function getAllTemplatesTest(ApiTester $I)
    {
        $I->wantTo('Get all templates');
        $I->sendGET('/templates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function createTemplateTest(ApiTester $I)
    {
        $I->wantTo('Create one template');
        $I->sendPOST('/templates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(array('result' => 'success'));
    }

    public function getOneTemplateTest(ApiTester $I)
    {
        $I->wantTo('Get one template');
        $I->sendGET('/templates/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function updateTemplateTest(ApiTester $I)
    {
        $I->wantTo('Update one template');
        $I->sendPOST('/templates/1', array('title' => 'new title', 'body' => 'new body', 'metadata' => '{"foo":1}'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(array('result' => 'success'));
    }
}
