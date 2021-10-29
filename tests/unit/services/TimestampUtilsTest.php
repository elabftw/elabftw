<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\EntityParams;
use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use function file_get_contents;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use RunTimeException;

class TimestampUtilsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
    }

    public function testTimestamp(): void
    {
        $fixturePaths = $this->getFixturePaths('dfn');
        $mockResponse = $this->readFile($fixturePaths['asn1']);
        $client = $this->getClient($mockResponse);

        $Maker = new MakeDfnTimestamp(array(), $this->getFreshTimestampableEntity());
        $tsUtils = new TimestampUtils($client, $fixturePaths['pdf'], $Maker->getTimestampParameters(), new TimestampResponse());
        $this->assertInstanceOf(TimestampResponse::class, $tsUtils->timestamp());
    }

    public function getFixturePaths(string $tsa): array
    {
        return array(
            'pdf' => dirname(__DIR__, 2) . '/_data/' . $tsa . '.pdf',
            'asn1' => dirname(__DIR__, 2) . '/_data/' . $tsa . '.asn1',
        );
    }

    private function readFile(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RunTimeException('Could not read fixture file!');
        }
        return $content;
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

    private function getFreshTimestampableEntity(): Experiments
    {
        $Entity = new Experiments(new Users(1, 1));
        // create a new experiment for timestamping tests
        $Entity->setId($Entity->create(new EntityParams('ts test')));
        // set it to a status that is timestampable
        $Entity->updateCategory(2);
        return $Entity;
    }
}
