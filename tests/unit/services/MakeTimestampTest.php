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
        $Maker = new MakeTimestamp($this->configArr, $Entity, $this->getClient(''));
        $this->expectException(ImproperActionException::class);
        $Maker->timestamp();
    }

    public function testGetFileName(): void
    {
        $Maker = new MakeTimestamp($this->configArr, $this->getFreshTimestampableEntity(), $this->getClient(''));
        $this->assertStringContainsString('-timestamped.pdf', $Maker->getFileName());
    }

    public function testDfnTimestamp(): void
    {
        $mockResponse = $this->readFixtureToken('dfn');
        $client = $this->getClient($mockResponse);
        $Maker = new MakeDfnTimestamp($this->configArr, $this->getFreshTimestampableEntity(), $client);
        $this->assertTrue($Maker->timestamp());
    }

    public function testUniversignTimestamp(): void
    {
        $mockResponse = $this->readFixtureToken('universign');
        $client = $this->getClient($mockResponse);
        $this->configArr['stamplogin'] = 'fakelogin@example.com';
        // create a fake encrypted password
        $this->configArr['ts_password'] = Crypto::encrypt('fakepassword', Key::loadFromAsciiSafeString(SECRET_KEY));
        $Maker = new MakeUniversignTimestamp($this->configArr, $this->getFreshTimestampableEntity(), $client);
        $this->assertTrue($Maker->timestamp());
    }

    private function readFixtureToken(string $tsa): string
    {
        $token = file_get_contents(dirname(__DIR__, 2) . '/_data/' . $tsa . '-token.asn1');
        if ($token === false) {
            throw new RunTimeException('Could not read fixture token!');
        }
        return $token;
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
