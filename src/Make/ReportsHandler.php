<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use DateTimeImmutable;
use DateTimeZone;
use Elabftw\Elabftw\Env;
use Elabftw\Enums\ReportScopes;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\AbstractRest;
use Elabftw\Models\Compounds;
use Elabftw\Models\StorageUnits;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use GuzzleHttp\Client;
use Override;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handle reports request
 */
final class ReportsHandler extends AbstractRest
{
    public function __construct(private Users $requester) {}

    public function getResponse(ReportScopes $scope, ?InputBag $query = null): Response
    {
        $httpGetter = new HttpGetter(new Client(), verifyTls: !Env::asBool('DEV_MODE'));
        $Reporter = match ($scope) {
            ReportScopes::Compounds => new MakeCompoundsReport(new Compounds($httpGetter, $this->requester, new NullFingerprinter(), false)),
            ReportScopes::CompoundsHistory => (
                function () use ($httpGetter, $query) {
                    $start = self::extractDateFromQuery($query, 'start', '10 years ago');
                    $end = self::extractDateFromQuery($query, 'end', 'now');
                    return new MakeCompoundsHistoryReport(
                        new Compounds($httpGetter, $this->requester, new NullFingerprinter(), false),
                        $start,
                        $end,
                    );
                }
            )(),
            ReportScopes::Instance => new MakeReport($this->requester),
            ReportScopes::Inventory => new MakeInventoryReport(new StorageUnits($this->requester, false)),
            ReportScopes::Team => new MakeTeamReport($this->requester),
            ReportScopes::StoredCompounds => new MakeStoredCompoundsReport(new StorageUnits($this->requester, false)),
        };
        return $Reporter->getResponse();

    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        return array('query_parameters' => array('format' => array('csv', 'json (not implemented)'), 'scope' => array('instance', 'team')));
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/reports/';
    }

    private static function extractDateFromQuery(?InputBag $query, string $param, string $defaultMoment): DateTimeImmutable
    {
        if ($query !== null && $query->has($param)) {
            // ! resets the hours/minutes to 0
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $query->getString($param), new DateTimeZone('UTC'));
            if ($date === false) {
                return new DateTimeImmutable($defaultMoment);
            }
            return $date;
        }
        return new DateTimeImmutable($defaultMoment);
    }
}
