<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Elabftw\CreateUploadFromLocalFile;
use Elabftw\Enums\Action;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users\Users;
use Elabftw\Services\MpdfProvider;
use Elabftw\Traits\TestsUtilsTrait;
use Monolog\Handler\NullHandler;
use Monolog\Logger;

class MakePdfTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private MakePdf $MakePdf;

    protected function setUp(): void
    {
        $requester = new Users(1, 1);
        // test >Append attached PDFs<
        (new Users(1, 1, new Users(1, 1)))->patch(Action::Update, array('append_pdfs' => 1));
        $Entity = new Experiments(new Users(1, 1), null);
        $new = $Entity->create();
        $Entity->setId($new);
        $Entity->canOrExplode('write');
        $entityData = $Entity->readOne();
        $body = $entityData['body_html'];
        // add invalid tex macro to body to cover notification being created upon failing mathjax
        $body .= '\n<p>$ \someInvalidTexMacro $</p>';
        // add a pdf
        $Entity->Uploads->create(new CreateUploadFromLocalFile('digicert.pdf', dirname(__DIR__, 2) . '/_data/digicert.pdf'));
        // add a pdf with password -> cannot be appended
        $Entity->Uploads->create(new CreateUploadFromLocalFile('with_password_123456.pdf', dirname(__DIR__, 2) . '/_data/with_password_123456.pdf'));
        // add an image to the body
        $id = $Entity->Uploads->create(new CreateUploadFromLocalFile('example.png', dirname(__DIR__, 2) . '/_data/example.png'));
        $Entity->Uploads->setId($id);
        $upArr = $Entity->Uploads->uploadData;
        $body .= '\n<p><img src="app/download.php?f=' . $upArr['long_name'] . '&amp;storage=' . $upArr['storage'] . '"></p>';
        // without storage part of the query to test getStorageFromLongname
        $body .= '\n<p><img src="app/download.php?f=' . $upArr['long_name'] . '"></p>';
        // test upper case file extension
        $id = $Entity->Uploads->create(new CreateUploadFromLocalFile('example.PNG', dirname(__DIR__, 2) . '/_data/example.png'));
        $Entity->Uploads->setId($id);
        $upArr = $Entity->Uploads->uploadData;
        $body .= '\n<p><img src="app/download.php?f=' . $upArr['long_name'] . '"></p>';

        $Entity->patch(Action::Update, array(
            'title' => 'Test Pdf',
            'date' => '20160729',
            'body' => $body,
        ));

        $MpdfProvider = new MpdfProvider('Toto');
        $log = (new Logger('elabftw'))->pushHandler(new NullHandler());
        $this->MakePdf = new MakePdf($log, $MpdfProvider, $requester, array($Entity, $this->getFreshExperiment()));
    }

    public function testGetFileContent(): void
    {
        $this->assertIsString($this->MakePdf->getFileContent());
    }

    public function testGetContentType(): void
    {
        $this->assertEquals('application/pdf', $this->MakePdf->getContentType());
    }

    /* TODO FIXME re-enable once AbstractConcreteEntity->timestamp() has maker and tsmaker in args so we can mock the request
    public function testEntityRenderContainsKeyDates(): void
    {
        $fresh = $this->getFreshExperiment();
        // lock and timestamp it, before unlocking it
        $fresh->lock();
        $fresh->patch(Action::Timestamp, array());
        $this->assertEquals(1, $fresh->entityData['locked']);
        $this->assertEquals(1, $fresh->entityData['timestamped']);
        $this->assertNotEquals('Unknown', $fresh->getLockerFullname());
        $this->assertNotEquals('Unknown', $fresh->getTimestamperFullname());

        $fresh->unlock();
        $this->assertEquals(0, $fresh->entityData['locked']);
        $this->assertEquals('Unknown', $fresh->getLockerFullname());

        // Destroy fresh experiment to keep the timestamp count stable for other tests
        // Refer to testGetTimestampThisMonth()
        $fresh->destroy();
    }
     */
}
