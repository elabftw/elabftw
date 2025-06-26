<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha Camara <mouss@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Traits;

use Elabftw\Interfaces\QueryParamsInterface;
use PDO;
use PDOStatement;

trait ApplyBindingsTrait
{
    /**
     * Bind all parameters from QueryParamsInterface into a PDOStatement.
     */
    protected function bindQueryParamsBindings(PDOStatement $stmt, QueryParamsInterface $queryParams): void
    {
        foreach ($queryParams->getBindings() as $param => $value) {
            $stmt->bindValue(":$param", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    }
}
