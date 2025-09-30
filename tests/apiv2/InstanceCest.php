<?php

declare(strict_types=1);

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

use Codeception\Util\HttpCode;

class InstanceCest
{
    public function _before(Apiv2Tester $I)
    {
        $I->haveHttpHeader('Authorization', 'apiKey4Test');
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    public function getTest(Apiv2Tester $I)
    {
        $I->wantTo('Send a GET on /instance');
        $I->sendGET('/instance');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }
}
