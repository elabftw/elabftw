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
use Elabftw\Models\StorageUnits;
use Elabftw\Models\Users;
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
        $Reporter = match ($scope) {
            ReportScopes::Instance => (new MakeReport($this->requester)),
            ReportScopes::Team => (new MakeTeamReport($this->requester)),
            ReportScopes::Storage => (new MakeStorageReport(new StorageUnits($this->requester))),
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
