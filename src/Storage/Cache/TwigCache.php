<?php

/**
 * @author Nicolas CARPi <Deltablot>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Storage\Cache;

use Elabftw\Services\TwigCacheGenerator;
use Override;
use Symfony\Component\Console\Output\NullOutput;

/**
 * For twig cached files
 */
class TwigCache extends AbstractCache
{
    protected const string FOLDER = '/run/elabftw/cache/twig';

    #[Override]
    public function warm(): bool
    {
        return new TwigCacheGenerator(self::FOLDER, new NullOutput())->warm();
    }
}
