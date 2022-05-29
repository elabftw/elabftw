<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

/**
 * Make an ELN archive
 */
class MakeEln extends MakeStreamZip
{
    protected string $extension = '.eln';

    /**
     * Loop on each id and add it to our zip archive
     * This could be called the main function.
     */
    public function getZip(): void
    {
        foreach ($this->idArr as $id) {
            $this->jsonArr[] = $id;
        }

        $root = 'ro-crate/';
        $this->Zip->addFile($root . 'ro-crate-metadata.json', json_encode($this->jsonArr, JSON_THROW_ON_ERROR, 512));
        $this->Zip->finish();
    }
}
