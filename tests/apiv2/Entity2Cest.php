<?php

declare(strict_types=1);

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

use Codeception\Util\HttpCode;

class Entity2Cest
{
    private string $expid = '';

    private string $itemid = '';

    public function _before(Apiv2Tester $I)
    {
        $I->haveHttpHeader('Authorization', 'apiKey4Test');
        $I->haveHttpHeader('Content-Type', 'application/json');
        if (empty($this->expid)) {
            $I->sendPOST('/experiments');
            $location = $I->grabHttpHeader('Location');
            $path = parse_url($location, PHP_URL_PATH);
            $this->expid = basename($path);
        }
        if (empty($this->itemid)) {
            $I->sendPOST('/items');
            $location = $I->grabHttpHeader('Location');
            $path = parse_url($location, PHP_URL_PATH);
            $this->itemid = basename($path);
        }
    }

    public function getAllExperimentsTest(Apiv2Tester $I)
    {
        $I->wantTo('Get experiments');
        $I->sendGET('/experiments');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function getOneExperimentTest(Apiv2Tester $I)
    {
        $I->wantTo('Get one experiment');
        $I->sendGET("/experiments/{$this->expid}");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
    }

    public function getAllItemsTest(Apiv2Tester $I)
    {
        $I->wantTo('Get all visible items');
        $I->sendGET('/items');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function improperFormatTest(Apiv2Tester $I)
    {
        $I->wantTo('Send an improper format');
        $I->sendGET("/experiments/{$this->expid}?format=docx");
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function getCsvTest(Apiv2Tester $I)
    {
        $I->wantTo('Get an experiment as csv');
        $I->sendGET("/experiments/{$this->expid}?format=csv");
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function getElnTest(Apiv2Tester $I)
    {
        $I->wantTo('Get the first experiment as eln');
        $I->sendGET("/experiments/{$this->expid}?format=eln");
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function getQrPdfTest(Apiv2Tester $I)
    {
        $I->wantTo('Get the first experiment as qrpdf');
        $I->sendGET("/experiments/{$this->expid}?format=qrpdf");
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function getPdfTest(Apiv2Tester $I)
    {
        $I->wantTo('Get the first experiment as PDF');
        $I->sendGET("/experiments/{$this->expid}?format=pdf");
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function getPdfATest(Apiv2Tester $I)
    {
        $I->wantTo('Get the first experiment as PDF/A');
        $I->sendGET("/experiments/{$this->expid}?format=pdfa");
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function getZipTest(Apiv2Tester $I)
    {
        $I->wantTo('Get the first experiment as ZIP');
        $I->sendGET("/experiments/{$this->expid}?format=zip");
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function getOneItemTest(Apiv2Tester $I)
    {
        $I->wantTo('Get an item');
        $I->sendGET("/items/{$this->itemid}");
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(array('content_type' => 1));
    }

    public function updateExpTest(Apiv2Tester $I)
    {
        $I->wantTo('Update an experiment');
        $I->sendPATCH("/experiments/{$this->expid}", array('title' => 'new title', 'date' => '20191231', 'body' => 'new body', 'metadata' => '{"foo":1}'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(array('title' => 'new title'));
        $I->seeResponseContainsJson(array('date' => '2019-12-31'));
        $I->seeResponseContainsJson(array('body' => 'new body'));
        $I->seeResponseContainsJson(array('metadata' => '{"foo": 1}'));
    }

    public function addTagTest(Apiv2Tester $I)
    {
        $I->wantTo('Add a tag to an experiment');
        $I->sendPOST("/experiments/{$this->expid}/tags", array('tag' => 'some tag'));
        $I->seeResponseCodeIs(HttpCode::CREATED); // 201
    }

    public function addLinkTest(Apiv2Tester $I)
    {
        $I->wantTo('Add a link to an experiment');
        $I->sendPOST("/experiments/{$this->expid}/items_links/1", array());
        $I->seeResponseCodeIs(HttpCode::CREATED); // 201
    }

    public function createExpTest(Apiv2Tester $I)
    {
        $I->wantTo('Create an experiment');
        $I->sendPOST('/experiments');
        $I->seeResponseCodeIs(HttpCode::CREATED); // 201
    }

    public function duplicateExpTest(Apiv2Tester $I)
    {
        $I->wantTo('Duplicate an experiment');
        $I->sendPOST("/experiments/{$this->expid}", array('action' => 'duplicate'));
        $I->seeResponseCodeIs(HttpCode::CREATED); // 201
    }

    public function duplicateItemTest(Apiv2Tester $I)
    {
        $I->wantTo('Duplicate an item');
        $I->sendPOST("/items/{$this->itemid}", array('action' => 'duplicate'));
        $I->seeResponseCodeIs(HttpCode::CREATED); // 201
    }

    public function createItemTest(Apiv2Tester $I)
    {
        $I->wantTo('Create an item');
        $I->sendPOST('/items', array('category_id' => 1));
        $I->seeResponseCodeIs(HttpCode::CREATED); // 201
    }

    public function improperAction(Apiv2Tester $I)
    {
        $I->wantTo('Send an improper request');
        // this should normally be a patch request
        $I->sendPOST("/experiments/{$this->expid}", array('action' => 'bloxberg'));
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function deleteItemTest(Apiv2Tester $I)
    {
        $I->wantTo('Delete an item');
        $I->sendDELETE("/items/{$this->itemid}");
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
    }

    public function deleteExperimentTest(Apiv2Tester $I)
    {
        $I->wantTo('Delete an experiment');
        $I->sendDELETE("/experiments/{$this->expid}");
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
    }

    public function resourceNotFoundTest(Apiv2Tester $I)
    {
        $I->wantTo('Find a non existing item');
        $I->sendGET('/items/196883');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function extendedSearchTest(Apiv2Tester $I)
    {
        $I->wantTo('Use extended parameter to get experiments');
        $I->sendPOST('/experiments');
        $location = $I->grabHttpHeader('Location');
        $path = parse_url($location, PHP_URL_PATH);
        $id = basename($path);
        $I->sendPatch("/experiments/{$id}", array('metadata' => '{"extra_fields": {"Raw data URL": {"type": "text", "value": "smb://yep"}}}'));
        $I->sendGET('/experiments/?extended=extrafield:"Raw data URL":%');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(array('id' => $id));
    }
}
