<?php

/**
 * @author Nicolas CARPi <Deltablot>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Storage;

/**
 * For twig cached files
 */
class TwigCache extends AbstractStorage
{
    protected const string FOLDER = '/run/elabftw/cache/twig';
}
