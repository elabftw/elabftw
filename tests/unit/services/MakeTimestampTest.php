<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\EntityParams;
use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Exceptions\ImproperActionException;
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
use const SECRET_KEY;

class MakeTimestampTest extends \PHPUnit\Framework\TestCase
{
    private array $configArr;

    protected function setUp(): void
    {
        $this->configArr = array(
            'proxy' => '',
        );
    }

    public function testNonTimestampableExperiment(): void
    {
        $Entity = new Experiments(new Users(1, 1));
        // create a new experiment for timestamping tests
        $Entity->setId($Entity->create(new EntityParams('ts test')));
        // default status is not timestampable
        $Maker = new MakeTimestamp($this->configArr, $Entity);
        $this->expectException(ImproperActionException::class);
        $Maker->generatePdf();
    }

    public function testGetFileName(): void
    {
        $Maker = new MakeTimestamp($this->configArr, $this->getFreshTimestampableEntity());
        $this->assertStringContainsString('-timestamped.pdf', $Maker->getFileName());
    }

    public function testDfnTimestamp(): void
    {
        $fixturePaths = $this->getFixturePaths('dfn');
        $mockResponse = $this->readFile($fixturePaths['asn1']);
        $client = $this->getClient($mockResponse);
        $Maker = new MakeDfnTimestamp($this->configArr, $this->getFreshTimestampableEntity());
        $Maker->generatePdf();
        // create a custom response object with fixture token
        $tsResponse = new TimestampResponse();
        $tsResponse->setTokenPath($fixturePaths['asn1']);
        $tsResponse->setTokenName('some-name');
        $this->assertTrue($Maker->saveTimestamp($tsResponse));
    }

    public function testDigicertTimestamp(): void
    {
        $fixturePaths = $this->getFixturePaths('digicert');
        $mockResponse = $this->readFile($fixturePaths['asn1']);
        $client = $this->getClient($mockResponse);
        $Maker = new MakeDigicertTimestamp($this->configArr, $this->getFreshTimestampableEntity());
        $Maker->generatePdf();
        // create a custom response object with fixture token
        $tsResponse = new TimestampResponse();
        $tsResponse->setTokenPath($fixturePaths['asn1']);
        $tsResponse->setTokenName('some-name');
        $this->assertTrue($Maker->saveTimestamp($tsResponse));
    }

    public function testUniversignTimestamp(): void
    {
        $fixturePaths = $this->getFixturePaths('universign');
        $mockResponse = $this->readFile($fixturePaths['asn1']);
        $client = $this->getClient($mockResponse);
        $config = array();
        $config['ts_login'] = 'fakelogin@example.com';
        // create a fake encrypted password
        $config['ts_password'] = Crypto::encrypt('fakepassword', Key::loadFromAsciiSafeString(SECRET_KEY));
        $Maker = new MakeUniversignTimestamp($config, $this->getFreshTimestampableEntity());
        $Maker->generatePdf();
        // create a custom response object with fixture token
        $tsResponse = new TimestampResponse();
        $tsResponse->setTokenPath($fixturePaths['asn1']);
        $tsResponse->setTokenName('some-name');
        $this->assertTrue($Maker->saveTimestamp($tsResponse));
    }

    public function testGlobalSign(): void
    {
        $Maker = new MakeGlobalSignTimestamp(array(), $this->getFreshTimestampableEntity());
    }

    public function testSectigo(): void
    {
        $Maker = new MakeSectigoTimestamp(array(), $this->getFreshTimestampableEntity());
    }

    public function testUniversignTimestampNoLogin(): void
    {
        $this->expectException(ImproperActionException::class);
        $Maker = new MakeUniversignTimestamp(array(), $this->getFreshTimestampableEntity());
    }

    public function testUniversignTimestampNoPassword(): void
    {
        $this->expectException(ImproperActionException::class);
        $Maker = new MakeUniversignTimestamp(array('ts_login' => 'some-login'), $this->getFreshTimestampableEntity());
    }

    public function testUniversignTimestampBadResponseTime(): void
    {
        $config = array();
        $fixturePaths = $this->getFixturePaths('universign');

        $config['ts_login'] = 'fakelogin@example.com';
        // create a fake encrypted password
        $config['ts_password'] = Crypto::encrypt('fakepassword', Key::loadFromAsciiSafeString(SECRET_KEY));
        $Maker = new MakeUniversignTimestamp($config, $this->getFreshTimestampableEntity());
        $Maker->generatePdf();
        // create a custom response object with fixture token
        $tsResponseMock = $this->createMock(TimestampResponse::class);
        $tsResponseMock->method('getTimestampFromResponseFile')->willReturn('2000');
        $tsResponseMock->method('getTokenPath')->willReturn($fixturePaths['asn1']);
        $tsResponseMock->method('getTokenName')->willReturn('some-name');
        $this->expectException(ImproperActionException::class);
        $Maker->saveTimestamp($tsResponseMock);
    }

    private function getFixturePaths(string $tsa): array
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
