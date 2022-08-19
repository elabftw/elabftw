<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

use \Codeception\Util\HttpCode;

class BadRequestsCest
{
    // Make a request without an authorization header
    public function noTokenTest(ApiTester $I)
    {
        $I->wantTo('Send a request without a token');
        // we need to delete it because it is set by other tests and codeception will keep it
        // for all subsequent requests
        $I->deleteHeader('Authorization');
        $I->sendGET('/experiments/1');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED); // 401
    }

    // Make a request with an invalid key
    public function badTokenTest(ApiTester $I)
    {
        $I->wantTo('Send a request with a wrong key');
        $I->haveHttpHeader('Authorization', 'wrong_key');
        $I->sendGET('/experiments/1');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST); // 400
    }

    public function badMethodTest(ApiTester $I)
    {
        $I->wantTo('Send a request with an invalid HTTP method');
        $I->haveHttpHeader('Authorization', 'apiKey4Test');
        $I->sendPUT('/experiments/1');
        $I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED); // 405
    }
}
