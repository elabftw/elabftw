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

use DateTimeImmutable;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Compounds;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use GuzzleHttp\Client;

class MakeCompoundsHistoryReportTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCsv(): void
    {
        $requester = new Users(1, 1);
        $httpGetter = new HttpGetter(new Client(), verifyTls: false);
        $this->expectException(ImproperActionException::class);
        new MakeCompoundsHistoryReport(
            new Compounds($httpGetter, $requester, new NullFingerprinter(), false),
            new DateTimeImmutable('10 year ago'),
            new DateTimeImmutable('now'),
        );
    }
}
