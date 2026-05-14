<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Interfaces;

/**
 * Interface for Idps
 */
interface IdpsInterface
{
    public function setId(?int $id): void;

    public function readAllSimpleEnabled(): array;

    public function readAllLight(): array;

    public function selectOne(): array;

    public function fullUpdate(array $idp): array;

    public function upsert(int $sourceId, array $idps): int;

    public function getEnabled(?int $id = null): int;

    public function getEnabledByEntityId(string $entId): int;

    public function findByEntityId(string $entityId): int;

    public function create(
        string $name,
        string $entityid,
        string $email_attr,
        ?string $team_attr,
        string $fname_attr,
        string $lname_attr,
        ?string $orgid_attr,
        int $enabled = 1,
        ?int $source = null,
        ?array $certs = array(),
        ?array $endpoints = array(),
    ): int;
}
