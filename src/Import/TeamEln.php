<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Import;

use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Models\Users;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Import a .eln file for a complete team
 */
class TeamEln extends AbstractZip
{
    private Eln $Importer;

    public function __construct(private int $userid, private int $teamid, private string $filePath, protected FilesystemOperator $fs)
    {
        $UploadedFile = new UploadedFile($this->filePath, 'input.eln', null, null, true);
        $this->Importer = new Eln(
            new Users($this->userid, $this->teamid),
            EntityType::Experiments,
            false,
            BasePermissions::Team->toJson(),
            BasePermissions::Team->toJson(),
            $UploadedFile,
            $this->fs,
        );
    }

    public function dryRun(): array
    {
        return $this->Importer->processOnly();
    }

    public function import(): void
    {
        $this->Importer->import();
    }
}
