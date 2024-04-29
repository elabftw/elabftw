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
use Elabftw\Enums\Action;

class UsersCest
{
    public function _before(Apiv2Tester $I)
    {
        $I->haveHttpHeader('Authorization', 'apiKey4Test');
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    public function disableMfaTest(Apiv2Tester $I)
    {
        $I->wantTo('Disable mfa for a user');
        // this user doesn't have 2fa but it's okay
        $I->sendPATCH('/users/2', array('action' => Action::Disable2fa->value));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function illegalDisableMfaTest(Apiv2Tester $I)
    {
        // use Tata (Bravo team Admin) to try and disable 2fa for another user in their team
        $I->haveHttpHeader('Authorization', 'apiKey4Test_tata');
        $I->wantTo('Disable mfa for a user but we should not be able to');
        $I->sendPATCH('/users/6', array('action' => Action::Disable2fa->value));
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN); // 403
        $I->seeResponseIsJson();
    }
}
