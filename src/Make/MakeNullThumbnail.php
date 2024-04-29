<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

/**
 * Skip trying to make a thumbnail
 */
final class MakeNullThumbnail extends MakeThumbnail
{
    public function saveThumb(): void
    {
        return;
    }
}
