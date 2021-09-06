<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Services\Filter;

final class UnfinishedStepsParams extends ContentParams implements ContentParamsInterface
{
    public function __construct(protected ?array $extra = null)
    {
    }

    public function getExtra(string $key): string
    {
        $scope = Filter::sanitize($this->extra[$key] ?? 'user');

        if (!($scope === 'user' || $scope === 'team')) {
            $scope = 'user';
        }

        return $scope;
    }
}
