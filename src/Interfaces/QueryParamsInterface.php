<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Interfaces;

use Elabftw\Enums\EntityType;
use Symfony\Component\HttpFoundation\InputBag;

interface QueryParamsInterface
{
    public function getSql(): string;

    public function getQuery(): InputBag;

    public function getLimit(): int;

    public function getStatesSql(string $tableName): string;

    public function isFast(): bool;

    public function getFastq(): string;

    public function hasUserQuery(): bool;

    public function getUserQuery(): string;

    public function getRelatedOrigin(): ?EntityType;

    public function getFilterSql(): string;

    public function setSkipOrderPinned(bool $value): void;
}
