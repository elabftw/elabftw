<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Enums\ExportFormat;
use Elabftw\Enums\Storage;
use Elabftw\Make\MakeDfnTimestamp;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\Flysystem\Filesystem;

class TimestampUtilsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Filesystem $fixturesFs;

    protected function setUp(): void
    {
        $this->fixturesFs = Storage::FIXTURES->getStorage()->getFs();
    }

    public function testTimestamp(): void
    {
        $mockResponse = $this->fixturesFs->read('dfn.asn1');
        $client = $this->getClient($mockResponse);

        $Maker = new MakeDfnTimestamp(
            new Users(1, 1),
            $this->getFreshExperiment(),
            array(),
            ExportFormat::Json,
        );
        $pdfBlob = $this->fixturesFs->read('dfn.pdf');
        $tsUtils = new TimestampUtils($client, $pdfBlob, $Maker->getTimestampParameters(), new TimestampResponse());
        $this->assertInstanceOf(TimestampResponse::class, $tsUtils->timestamp());
    }

    private function getClient(string $mockResponse): Client
    {
        // don't use the real guzzle client, but use a mock
        // https://docs.guzzlephp.org/en/stable/testing.html
        $mock = new MockHandler(array(
            new Response(200, array(), $mockResponse),
            new RequestException('Server is down?', new Request('GET', 'test')),
        ));
        $handlerStack = HandlerStack::create($mock);
        return new Client(array('handler' => $handlerStack));
    }
}
