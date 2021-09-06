<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\UnfinishedStepsParamsInterface;
use Elabftw\Services\Filter;

final class UnfinishedStepsParams extends ContentParams implements UnfinishedStepsParamsInterface
{
    public function __construct(private ?array $extra = null)
    {
    }

    public function getScope(): string
    {
        $scope = Filter::sanitize($this->extra['scope'] ?? 'user');

        if (!($scope === 'user'
            || $scope === 'team')) {
            throw new ImproperActionException(Tools::error());
        }

        return $scope;
    }
}
