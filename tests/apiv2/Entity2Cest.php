<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

use \Codeception\Util\HttpCode;

class Entity2Cest
{
    public function _before(ApiTester $I)
    {
        $I->haveHttpHeader('Authorization', 'apiKey4Test');
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    public function getAllExpTest(ApiTester $I)
    {
        $I->wantTo('Get all visible experiments');
        $I->sendGET('/experiments');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function getAllItemsTest(ApiTester $I)
    {
        $I->wantTo('Get all visible items');
        $I->sendGET('/items');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function getOneExpTest(ApiTester $I)
    {
        $I->wantTo('Get the first experiment');
        $I->sendGET('/experiments/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function getOneItemTest(ApiTester $I)
    {
        $I->wantTo('Get the first item');
        $I->sendGET('/items/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(array('content_type' => 1));
    }

    public function updateExpTest(ApiTester $I)
    {
        $I->wantTo('Update an experiment');
        $I->sendPATCH('/experiments/1', array('title' => 'new title', 'date' => '20191231', 'body' => 'new body', 'metadata' => '{"foo":1}'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(array('title' => 'new title'));
        $I->seeResponseContainsJson(array('date' => '2019-12-31'));
        $I->seeResponseContainsJson(array('body' => 'new body'));
        $I->seeResponseContainsJson(array('metadata' => '{"foo": 1}'));
    }

    public function addTagTest(ApiTester $I)
    {
        $I->wantTo('Add a tag to an experiment');
        $I->sendPOST('/experiments/1/tags', array('tag' => 'some tag'));
        $I->seeResponseCodeIs(HttpCode::CREATED); // 201
    }

    public function addLinkTest(ApiTester $I)
    {
        $I->wantTo('Add a link to an experiment');
        $I->sendPOST('/experiments/1/links/1');
        $I->seeResponseCodeIs(HttpCode::CREATED); // 201
    }

    public function createExpTest(ApiTester $I)
    {
        $I->wantTo('Create an experiment');
        $I->sendPOST('/experiments');
        $I->seeResponseCodeIs(HttpCode::CREATED); // 201
    }

    public function createItemTest(ApiTester $I)
    {
        $I->wantTo('Create an item');
        $I->sendPOST('/items', array('category_id' => 1));
        $I->seeResponseCodeIs(HttpCode::CREATED); // 201
    }

    public function resourceNotFoundTest(ApiTester $I)
    {
        $I->wantTo('Find a non existing resource');
        $I->sendGET('/items/9001');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }
}
