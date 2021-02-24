<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

use \Codeception\Util\HttpCode;

class UploadsCest
{
    public function _before(ApiTester $I)
    {
        $I->haveHttpHeader('Authorization', 'apiKey4Test');
    }

    public function fileUploadTest(ApiTester $I)
    {
        $I->wantTo('Upload a file');
        $I->sendPOST('/experiments/1', array('inline' => 0), array('file' => codecept_data_dir('example.txt')));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson(array('result' => 'success'));
    }

    public function fileGetTest(ApiTester $I)
    {
        $I->wantTo('Get an uploaded file');
        $I->sendGET('/uploads/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeBinaryResponseEquals('f528056fad5fc8f63b71ef6e1572d003');
    }
}
