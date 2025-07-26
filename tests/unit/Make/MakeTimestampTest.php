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

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\CreateUploadFromLocalFile;
use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Enums\ExportFormat;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

class MakeTimestampTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private array $configArr;

    private string $dataPath;

    private string $comment = 'Timestamp archive by unit test';

    private ExportFormat $dataFormat = ExportFormat::Json;

    protected function setUp(): void
    {
        $this->configArr = array(
            'proxy' => '',
            'ts_limit' => '0',
        );
        $this->dataPath = dirname(__DIR__, 2) . '/_data/';
    }

    public function testTimestampLimitReached(): void
    {
        $configArr = array(
            'proxy' => '',
            'ts_limit' => '-1',
        );
        $this->expectException(ImproperActionException::class);
        new MakeDfnTimestamp(
            new Users(1, 1),
            $this->getFreshExperiment(),
            $configArr,
            $this->dataFormat,
        );
    }

    public function testGetFileName(): void
    {
        $Maker = new MakeDfnTimestamp(
            new Users(1, 1),
            $this->getFreshExperiment(),
            $this->configArr,
            $this->dataFormat,
        );
        $this->assertStringContainsString('-timestamped.zip', $Maker->getFileName());
    }

    public function testCustomTimestamp(): void
    {
        $configArr = array(
            'proxy' => '',
            'ts_limit' => '0',
            'ts_login' => '',
            'ts_password' => Crypto::encrypt('fakepassword', Key::loadFromAsciiSafeString(Config::fromEnv('SECRET_KEY'))),
            'ts_url' => 'https://ts.example.com',
            'ts_cert' => 'dummy.crt',
            'ts_hash' => 'sha1337',
        );
        $Maker = new MakeCustomTimestamp(
            new Users(1, 1),
            $this->getFreshExperiment(),
            $configArr,
            $this->dataFormat,
        );
        $this->assertIsArray($Maker->getTimestampParameters());
    }

    public function testDfnTimestamp(): void
    {
        $Maker = new MakeDfnTimestamp(
            new Users(1, 1),
            $this->getFreshExperiment(),
            $this->configArr,
            ExportFormat::Pdf,
        );
        $Maker->generateData();
        $this->assertIsArray($Maker->getTimestampParameters());
        /** @var \Elabftw\Elabftw\TimestampResponse&\PHPUnit\Framework\MockObject\MockObject $tsResponseMock */
        $tsResponseMock = $this->getMockBuilder(TimestampResponse::class)->getMock();
        $tsResponseMock->method('getTimestampFromResponseFile')->willReturn('Oct 17 05:12:18 2021 GMT');
        $zipName = $Maker->getFileName();
        $this->assertIsInt($Maker->saveTimestamp($tsResponseMock, new CreateUploadFromLocalFile($zipName, $this->dataPath . 'example.zip', $this->comment, 1, State::Archived)));
    }

    public function testDigicertTimestamp(): void
    {
        $Maker = new MakeDigicertTimestamp(
            new Users(1, 1),
            $this->getFreshExperiment(),
            $this->configArr,
            $this->dataFormat,
        );
        $Maker->generateData();
        $this->assertIsArray($Maker->getTimestampParameters());

        /** @var \Elabftw\Elabftw\TimestampResponse&\PHPUnit\Framework\MockObject\MockObject $tsResponseMock */
        $tsResponseMock = $this->getMockBuilder(TimestampResponse::class)->getMock();
        $tsResponseMock->method('getTimestampFromResponseFile')->willReturn('Oct 17 05:12:18 2021 GMT');
        $zipName = $Maker->getFileName();
        $this->assertIsInt($Maker->saveTimestamp($tsResponseMock, new CreateUploadFromLocalFile($zipName, $this->dataPath . 'example.zip', $this->comment, 1, State::Archived)));
    }

    public function testUniversignTimestamp(): void
    {
        $config = array(
            'ts_login' => 'fakelogin@example.com',
            // create a fake encrypted password
            'ts_password' => Crypto::encrypt('fakepassword', Key::loadFromAsciiSafeString(Config::fromEnv('SECRET_KEY'))),
        );
        $Maker = new MakeUniversignTimestamp(
            new Users(1, 1),
            $this->getFreshExperiment(),
            $config,
            $this->dataFormat,
        );
        $Maker->generateData();
        $this->assertIsArray($Maker->getTimestampParameters());

        /** @var \Elabftw\Elabftw\TimestampResponse&\PHPUnit\Framework\MockObject\MockObject $tsResponseMock */
        $tsResponseMock = $this->getMockBuilder(TimestampResponse::class)->getMock();
        $tsResponseMock->method('getTimestampFromResponseFile')->willReturn('Oct 17 13:37:42.666 2021 GMT');
        $zipName = $Maker->getFileName();
        $this->assertIsInt($Maker->saveTimestamp($tsResponseMock, new CreateUploadFromLocalFile($zipName, $this->dataPath . 'example.zip', $this->comment, 1, State::Archived)));
    }

    public function testGlobalSign(): void
    {
        $Maker = new MakeGlobalSignTimestamp(
            new Users(1, 1),
            $this->getFreshExperiment(),
            $this->configArr,
            $this->dataFormat,
        );
        $this->assertIsArray($Maker->getTimestampParameters());
    }

    public function testDgn(): void
    {
        $config = array();
        $config['ts_login'] = 'fakelogin@example.com';
        // create a fake encrypted password
        $config['ts_password'] = Crypto::encrypt('fakepassword', Key::loadFromAsciiSafeString(Config::fromEnv('SECRET_KEY')));
        $Maker = new MakeDgnTimestamp(
            new Users(1, 1),
            $this->getFreshExperiment(),
            $config,
            $this->dataFormat,
        );
        $this->assertIsArray($Maker->getTimestampParameters());
    }

    public function testSectigo(): void
    {
        $Maker = new MakeSectigoTimestamp(
            new Users(1, 1),
            $this->getFreshExperiment(),
            $this->configArr,
            $this->dataFormat,
        );
        $this->assertIsArray($Maker->getTimestampParameters());
    }

    public function testUniversignTimestampNoLogin(): void
    {
        $Maker = new MakeUniversignTimestamp(
            new Users(1, 1),
            $this->getFreshExperiment(),
            array(),
            $this->dataFormat,
        );
        $this->expectException(ImproperActionException::class);
        $Maker->getTimestampParameters();
    }

    public function testUniversignTimestampNoPassword(): void
    {
        $config = array('ts_login' => 'some-login');
        $Maker = new MakeUniversignTimestamp(
            new Users(1, 1),
            $this->getFreshExperiment(),
            $config,
            $this->dataFormat,
        );
        $this->expectException(ImproperActionException::class);
        $Maker->getTimestampParameters();
    }

    public function testUniversignTimestampBadResponseTime(): void
    {
        $config = array();

        $config['ts_login'] = 'fakelogin@example.com';
        // create a fake encrypted password
        $config['ts_password'] = Crypto::encrypt('fakepassword', Key::loadFromAsciiSafeString(Config::fromEnv('SECRET_KEY')));
        $Maker = new MakeUniversignTimestamp(
            new Users(1, 1),
            $this->getFreshExperiment(),
            $config,
            $this->dataFormat,
        );
        $Maker->generateData();
        /** @var \Elabftw\Elabftw\TimestampResponse&\PHPUnit\Framework\MockObject\MockObject $tsResponseMock */
        $tsResponseMock = $this->getMockBuilder(TimestampResponse::class)->getMock();
        $tsResponseMock->method('getTimestampFromResponseFile')->willReturn('yestermorrow');
        $this->expectException(ImproperActionException::class);
        $Maker->saveTimestamp($tsResponseMock, new CreateUploadFromLocalFile('realName', 'longName', $this->comment, 1, State::Archived));
    }
}
