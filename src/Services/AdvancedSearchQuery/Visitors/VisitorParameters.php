<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Visitors;

final class VisitorParameters
{
    public function __construct(private string $entityType, private array $teamGroups) {}

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getTeamGroups(): array
    {
        return $this->teamGroups;
    }
}
