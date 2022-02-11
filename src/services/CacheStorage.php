<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

/**
 * For local cache folder, used by many processes that need to write files at some point
 */
class CacheStorage extends LocalStorage
{
    protected const FOLDER = 'cache/elab';
}
