<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Storage;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Override;

/**
 * The cache folder parent of all caches
 */
final class ParentCache extends AbstractStorage
{
    protected const string FOLDER = '/run/elabftw/cache';

    public function destroy(): bool
    {
        $fs = $this->getFs();
        // skip deleting advancedSearchQuery because it's problematic with the generated grammar class in there
        $dirs = array('elab', 'twig', 'mpdf', 'purifier');
        foreach ($dirs as $dir) {
            $fs->deleteDirectory($dir);
        }
        return true;
    }

    #[Override]
    protected function getAdapter(): FilesystemAdapter
    {
        return new LocalFilesystemAdapter(static::FOLDER);
    }
}
