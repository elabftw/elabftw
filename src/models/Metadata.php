<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use function file_get_contents;

class Metadata
{
    public AbstractEntity $Entity;

    public function __construct(AbstractEntity $entity)
    {
        $this->Entity = $entity;
    }

    public function read(): string
    {
        $metadata = $this->Entity->Uploads->getMetadataFile();
        return file_get_contents($metadata->getFile()->getPathName());
    }
}
