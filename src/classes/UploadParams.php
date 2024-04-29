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

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Filter;

final class UploadParams extends ContentParams
{
    public function getContent(): mixed
    {
        return match ($this->target) {
            'real_name' => $this->getRealName(),
            'comment' => Filter::title($this->content),
            'state' => $this->getInt(),
            default => throw new ImproperActionException('Incorrect upload parameter.'),
        };
    }

    private function getRealName(): string
    {
        // don't allow php extension
        $ext = Tools::getExt($this->content);
        if ($ext === 'php') {
            throw new ImproperActionException('No php extension allowed!');
        }
        return Filter::forFilesystem($this->content);
    }
}
