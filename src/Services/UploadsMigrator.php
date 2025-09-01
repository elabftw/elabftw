<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Storage;
use League\Flysystem\FilesystemOperator;
use PDO;
use Symfony\Component\Console\Output\OutputInterface;

use function count;

/**
 * Migrate uploads to S3
 */
final class UploadsMigrator
{
    private Db $Db;

    public function __construct(private OutputInterface $output, private FilesystemOperator $sourceFs, private FilesystemOperator $targetFs)
    {
        $this->Db = Db::getConnection();
    }

    public function migrate(): int
    {
        $sql = 'UPDATE uploads SET storage = :storage WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':storage', Storage::S3->value);

        $localFiles = $this->findLocal();
        foreach ($localFiles as $upload) {
            $this->output->writeln(sprintf('↑ Uploading %s (%d bytes) ID: %d', $upload['real_name'], $upload['filesize'], $upload['id']));
            $this->targetFs->writeStream($upload['long_name'], $this->sourceFs->readStream($upload['long_name']));
            // also upload the thumbnail if it exists
            if ($this->sourceFs->fileExists($upload['long_name'] . '_th.jpg')) {
                $this->targetFs->writeStream($upload['long_name'] . '_th.jpg', $this->sourceFs->readStream($upload['long_name'] . '_th.jpg'));
            }
            $req->bindParam(':id', $upload['id'], PDO::PARAM_INT);
            $this->Db->execute($req);
        }
        return count($localFiles);
    }

    public function fixBodies(): void
    {
        $this->fixBody('experiments');
        $this->fixBody('items');
        $this->fixBody('experiments_templates');
        $this->fixBody('items_types');
    }

    private function fixBody(string $table): bool
    {
        // explicitly set the modified_at to the same value so we do not impact it with this command
        $sql = sprintf(
            "UPDATE %s SET body = REPLACE(body, 'storage=%d', 'storage=%d'), modified_at = modified_at WHERE body LIKE '%%storage=%d%%'",
            $table,
            Storage::LOCAL->value,
            Storage::S3->value,
            Storage::LOCAL->value,
        );
        $req = $this->Db->prepare($sql);
        return $this->Db->execute($req);
    }

    /**
     * Get an array of uploads stored locally
     */
    private function findLocal(): array
    {
        $sql = 'SELECT long_name, id, filesize, real_name FROM uploads WHERE storage = :storage';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':storage', Storage::LOCAL->value);
        $this->Db->execute($req);

        return $req->fetchAll();
    }
}
