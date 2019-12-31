<?php declare(strict_types=1);

use \Codeception\Util\HttpCode;

class ExperimentsCest
{
    public function _before(ApiTester $I)
    {
        $I->haveHttpHeader('Authorization', 'apiKey4Test');
    }

    public function getExperimentsTest(ApiTester $I)
    {
        $I->wantTo('Get all visible experiments');
        $I->sendGET('/experiments');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function getExperimentTest(ApiTester $I)
    {
        $I->wantTo('Get the first experiment');
        $I->sendGET('/experiments/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
