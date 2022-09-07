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

    public function improperFormatTest(ApiTester $I)
    {
        $I->wantTo('Send an improper format');
        $I->sendGET('/experiments/1?format=docx');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function getCsvTest(ApiTester $I)
    {
        $I->wantTo('Get the first experiment as csv');
        $I->sendGET('/experiments/1?format=csv');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function getElnTest(ApiTester $I)
    {
        $I->wantTo('Get the first experiment as eln');
        $I->sendGET('/experiments/1?format=eln');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function getQrPdfTest(ApiTester $I)
    {
        $I->wantTo('Get the first experiment as qrpdf');
        $I->sendGET('/experiments/1?format=qrpdf');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function getPdfTest(ApiTester $I)
    {
        $I->wantTo('Get the first experiment as PDF');
        $I->sendGET('/experiments/1?format=pdf');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function getPdfATest(ApiTester $I)
    {
        $I->wantTo('Get the first experiment as PDF/A');
        $I->sendGET('/experiments/1?format=pdfa');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function getZipTest(ApiTester $I)
    {
        $I->wantTo('Get the first experiment as ZIP');
        $I->sendGET('/experiments/1?format=zip');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
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

    public function duplicateExpTest(ApiTester $I)
    {
        $I->wantTo('Duplicate an experiment');
        $I->sendPOST('/experiments/1', array('action' => 'duplicate'));
        $I->seeResponseCodeIs(HttpCode::CREATED); // 201
    }

    public function duplicateItemTest(ApiTester $I)
    {
        $I->wantTo('Duplicate an item');
        $I->sendPOST('/items/1', array('action' => 'duplicate'));
        $I->seeResponseCodeIs(HttpCode::CREATED); // 201
    }

    public function createItemTest(ApiTester $I)
    {
        $I->wantTo('Create an item');
        $I->sendPOST('/items', array('category_id' => 1));
        $I->seeResponseCodeIs(HttpCode::CREATED); // 201
    }

    public function improperAction(ApiTester $I)
    {
        $I->wantTo('Send an improper request');
        // this should normally be a patch request
        $I->sendPOST('/experiments/1', array('action' => 'bloxberg'));
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function deleteItemTest(ApiTester $I)
    {
        $I->wantTo('Delete an item');
        $I->sendDELETE('/items/4');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
    }

    public function resourceNotFoundTest(ApiTester $I)
    {
        $I->wantTo('Find a non existing item');
        $I->sendGET('/items/9001');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }
}
