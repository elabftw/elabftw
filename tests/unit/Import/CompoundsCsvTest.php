<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Import;

use Elabftw\Enums\Action;
use Elabftw\Enums\Storage;
use Elabftw\Models\Compounds;
use Elabftw\Models\Items;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use const UPLOAD_ERR_OK;

class CompoundsCsvTest extends \PHPUnit\Framework\TestCase
{
    public function testImport(): void
    {
        $requester = new Users(1, 1);
        $Items = new Items($requester);
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/compounds.csv',
            'compounds.csv',
            null,
            UPLOAD_ERR_OK,
            true,
        );
        $fixturesFs = Storage::FIXTURES->getStorage()->getFs();
        $cidJson = $fixturesFs->read('cid-3345.json');
        $mock = new MockHandler(array(
            new Response(200, array(), $cidJson),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $httpGetter = new HttpGetter($client, '', false);
        $Compounds = new Compounds($httpGetter, $requester, new NullFingerprinter(), false);
        $cid = 3345;
        $compoundId = $Compounds->postAction(Action::Duplicate, array('cid' => $cid));
        $Compounds->setId($compoundId);
        $Import = new CompoundsCsv(new NullOutput(), $Items, $uploadedFile, $Compounds, 1);
        $this->assertTrue($Import->import() > 1);
    }
}
