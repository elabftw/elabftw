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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\Flysystem\Filesystem;
use const SECRET_KEY;

class MakeTimestampTest extends \PHPUnit\Framework\TestCase
{
    private array $configArr;

    private string $dataPath;

    private Filesystem $fixturesFs;

    protected function setUp(): void
    {
        $this->configArr = array(
            'proxy' => '',
            'ts_limit' => '0',
        );
        $this->dataPath = dirname(__DIR__, 2) . '/_data/';
        $this->fixturesFs = (new StorageFactory(StorageFactory::FIXTURES))->getStorage()->getFs();
    }

    public function testTimestampLimitReached(): void
    {
        $configArr = array(
            'proxy' => '',
            'ts_limit' => '-1',
        );
        $this->expectException(ImproperActionException::class);
        $Maker = new MakeDfnTimestamp($configArr, $this->getFreshTimestampableEntity());
    }

    public function testGetFileName(): void
    {
        $Maker = new MakeDfnTimestamp($this->configArr, $this->getFreshTimestampableEntity());
        $this->assertStringContainsString('-timestamped.zip', $Maker->getFileName());
    }

    public function testCustomTimestamp(): void
    {
        $configArr = array(
            'proxy' => '',
            'ts_limit' => '0',
            'ts_login' => '',
            'ts_password' => Crypto::encrypt('fakepassword', Key::loadFromAsciiSafeString(SECRET_KEY)),
            'ts_url' => 'https://ts.example.com',
            'ts_cert' => 'dummy.crt',
            'ts_hash' => 'sha1337',
        );
        $Maker = new MakeCustomTimestamp($configArr, $this->getFreshTimestampableEntity());
        $this->assertIsArray($Maker->getTimestampParameters());
    }

    public function testDfnTimestamp(): void
    {
        $mockResponse = $this->fixturesFs->read('dfn.asn1');
        $client = $this->getClient($mockResponse);
        $Maker = new MakeDfnTimestamp($this->configArr, $this->getFreshTimestampableEntity());
        $Maker->generatePdf();
        $this->assertIsArray($Maker->getTimestampParameters());
        // create a custom response object with fixture token
        $tsResponse = new TimestampResponse();
        $tsResponse->setTokenPath($this->dataPath . 'dfn.asn1');
        $this->assertIsInt($Maker->saveTimestamp($this->dataPath . 'dfn.pdf', $tsResponse));
    }

    public function testDigicertTimestamp(): void
    {
        $mockResponse = $this->fixturesFs->read('digicert.asn1');
        $client = $this->getClient($mockResponse);
        $Maker = new MakeDigicertTimestamp($this->configArr, $this->getFreshTimestampableEntity());
        $Maker->generatePdf();
        $this->assertIsArray($Maker->getTimestampParameters());
        // create a custom response object with fixture token
        $tsResponse = new TimestampResponse();
        $tsResponse->setTokenPath($this->dataPath . 'digicert.asn1');
        $this->assertIsInt($Maker->saveTimestamp($this->dataPath . 'digicert.pdf', $tsResponse));
    }

    public function testUniversignTimestamp(): void
    {
        $mockResponse = $this->fixturesFs->read('universign.asn1');
        $client = $this->getClient($mockResponse);
        $config = array(
            'ts_login' => 'fakelogin@example.com',
            // create a fake encrypted password
            'ts_password' => Crypto::encrypt('fakepassword', Key::loadFromAsciiSafeString(SECRET_KEY)),
        );
        $Maker = new MakeUniversignTimestamp($config, $this->getFreshTimestampableEntity());
        $Maker->generatePdf();
        $this->assertIsArray($Maker->getTimestampParameters());
        // create a custom response object with fixture token
        $tsResponse = new TimestampResponse();
        $tsResponse->setTokenPath($this->dataPath . 'universign.asn1');
        $this->assertIsInt($Maker->saveTimestamp($this->dataPath . 'universign.pdf', $tsResponse));
    }

    public function testGlobalSign(): void
    {
        $Maker = new MakeGlobalSignTimestamp(array(), $this->getFreshTimestampableEntity());
        $this->assertIsArray($Maker->getTimestampParameters());
    }

    public function testSectigo(): void
    {
        $Maker = new MakeSectigoTimestamp(array(), $this->getFreshTimestampableEntity());
        $this->assertIsArray($Maker->getTimestampParameters());
    }

    public function testUniversignTimestampNoLogin(): void
    {
        $Maker = new MakeUniversignTimestamp(array(), $this->getFreshTimestampableEntity());
        $this->expectException(ImproperActionException::class);
        $Maker->getTimestampParameters();
    }

    public function testUniversignTimestampNoPassword(): void
    {
        $Maker = new MakeUniversignTimestamp(array('ts_login' => 'some-login'), $this->getFreshTimestampableEntity());
        $this->expectException(ImproperActionException::class);
        $Maker->getTimestampParameters();
    }

    public function testUniversignTimestampBadResponseTime(): void
    {
        $config = array();

        $config['ts_login'] = 'fakelogin@example.com';
        // create a fake encrypted password
        $config['ts_password'] = Crypto::encrypt('fakepassword', Key::loadFromAsciiSafeString(SECRET_KEY));
        $Maker = new MakeUniversignTimestamp($config, $this->getFreshTimestampableEntity());
        $Maker->generatePdf();
        // create a custom response object with fixture token
        $tsResponseMock = $this->createMock(TimestampResponse::class);
        $tsResponseMock->method('getTimestampFromResponseFile')->willReturn('2000');
        $tsResponseMock->method('getTokenPath')->willReturn($this->dataPath . 'universign.asn1');
        $this->expectException(ImproperActionException::class);
        $Maker->saveTimestamp($this->dataPath . 'universign.pdf', $tsResponseMock);
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
        return $Entity;
    }
}
