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

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UpdateParams extends ContentParams
{
    public function getColor(): string
    {
        return 'Nope';
    }

    public function getIsTimestampable(): int
    {
        return 0;
    }

    public function getIsDefault(): int
    {
        return 0;
    }

    public function getTitle(): string
    {
        return 'no';
    }

    public function getDate(): string
    {
        return 'no';
    }

    public function getBody(): string
    {
        return 'no';
    }

    public function getCanread(): string
    {
        return 'team';
    }

    public function getCanwriteS(): string
    {
        return 'team';
    }

    public function getIsBookable(): int
    {
        return 0;
    }

    public function getTeam(): int
    {
        return 0;
    }

    public function getFile(): UploadedFile
    {
        return new UploadedFile('a', 'b');
    }
}
