<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Traits\QueryParamsTrait;
use Override;

/**
 * Base class for all RestInterface classes
 */
abstract class AbstractRest implements RestInterface
{
    use QueryParamsTrait;

    protected Db $Db;

    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        throw new ImproperActionException('No PATCH action for this endpoint!');
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        throw new ImproperActionException('No GET action for this endpoint!');
    }

    #[Override]
    public function readOne(): array
    {
        return $this->readAll();
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        throw new ImproperActionException('No POST action for this endpoint!');
    }

    #[Override]
    public function destroy(): bool
    {
        throw new ImproperActionException('No DELETE action for this endpoint!');
    }
}
