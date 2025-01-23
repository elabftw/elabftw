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

use Elabftw\Enums\ReportScopes;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\AbstractRest;
use Elabftw\Models\Compounds;
use Elabftw\Models\StorageUnits;
use Elabftw\Models\Users;
use Elabftw\Services\HttpGetter;
use GuzzleHttp\Client;
use Override;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handle reports request
 */
class ReportsHandler extends AbstractRest
{
    public function __construct(private Users $requester) {}

    public function getResponse(ReportScopes $scope): Response
    {
        $httpGetter = new HttpGetter(new Client(), verifyTls: false);
        $Reporter = match ($scope) {
            ReportScopes::Compounds => (new MakeCompoundsReport(new Compounds($httpGetter, $this->requester))),
            ReportScopes::Instance => (new MakeReport($this->requester)),
            ReportScopes::Inventory => (new MakeInventoryReport(new StorageUnits($this->requester))),
            ReportScopes::Team => (new MakeTeamReport($this->requester)),
            ReportScopes::StoredCompounds => (new MakeStoredCompoundsReport(new StorageUnits($this->requester))),
        };
        return $Reporter->getResponse();

    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        return array('query_parameters' => array('format' => array('csv', 'json (not implemented)'), 'scope' => array('instance', 'team')));
    }

    public function getApiPath(): string
    {
        return 'api/v2/reports/';
    }
}
