<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Storage\Cache;

/**
 * For local cache folder, used by many processes that need to write files at some point
 */
class ElabCache extends ParentCache
{
    protected const string FOLDER = parent::FOLDER . '/elab';
}
