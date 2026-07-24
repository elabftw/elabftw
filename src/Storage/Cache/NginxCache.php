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
 * For nginx temporary files, should be bind-mounted to host, not in tmpfs
 */
class NginxCache extends AbstractCache
{
    protected const string FOLDER = '/var/cache/nginx';
}
