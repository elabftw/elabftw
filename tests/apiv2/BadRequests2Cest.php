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

class BadRequests2Cest
{
    // Make a request with an invalid key
    public function badTokenTest(Apiv2Tester $I)
    {
        $I->wantTo('Send a request with a wrong key');
        $I->haveHttpHeader('Authorization', 'wrong_key');
        $I->sendGET('/experiments/1');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED); // 401
    }

    public function badMethodTest(Apiv2Tester $I)
    {
        $I->wantTo('Send a request with an invalid HTTP method');
        $I->haveHttpHeader('Authorization', 'apiKey4Test');
        $I->haveHttpHeader('Content-type', 'application/json');
        $I->sendPUT('/experiments/1');
        $I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED); // 405
    }
}
