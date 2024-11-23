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

use Elabftw\Enums\Action;
use Elabftw\Enums\ReportScopes;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Models\Users;
use Elabftw\Traits\QueryParamsTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handle reports request
 */
class ReportsHandler implements RestInterface
{
    use QueryParamsTrait;

    public function __construct(private Users $requester) {}

    public function readOne(): array
    {
        return $this->readAll();
    }

    public function getResponse(ReportScopes $scope): Response
    {
        $Reporter = match ($scope) {
            ReportScopes::Instance => (new MakeReport($this->requester)),
            ReportScopes::Team => (new MakeTeamReport($this->requester)),
        };
        return $Reporter->getResponse();

    }

    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        return array('query_parameters' => array('format' => array('csv', 'json (not implemented)'), 'scope' => array('instance', 'team')));
    }

    public function postAction(Action $action, array $reqBody): int
    {
        throw new ImproperActionException('Error: only GET method allowed.');
    }

    public function patch(Action $action, array $params): array
    {
        throw new ImproperActionException('Error: only GET method allowed.');
    }

    public function getApiPath(): string
    {
        return 'api/v2/reports/';
    }

    public function destroy(): bool
    {
        throw new ImproperActionException('Error: only GET method allowed.');
    }
}
