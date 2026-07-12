<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Storage;

/**
 * For locally stored uploads
 */
class Local extends AbstractStorage
{
    protected const string FOLDER = '/var/lib/elabftw/uploads';
}
