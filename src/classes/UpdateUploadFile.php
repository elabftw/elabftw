<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Interfaces\UpdateParamsInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UpdateUploadFile extends UpdateParams implements UpdateParamsInterface
{
    private UploadedFile $file;

    public function __construct(UploadedFile $file)
    {
        $this->file = $file;
        $this->target = 'file';
    }

    public function getFile(): UploadedFile
    {
        return $this->file;
    }
}
