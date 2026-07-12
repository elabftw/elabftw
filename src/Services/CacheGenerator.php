<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\FsTools;
use Elabftw\Traits\TwigTrait;
use League\Flysystem\StorageAttributes;
use RuntimeException;

use function dirname;
use function is_dir;
use function mkdir;
use function sprintf;

/**
 * Generate Twig cache
 */
final class CacheGenerator
{
    use TwigTrait;

    /**
     * Generate a twig cache file for all the templates in the template dir
     * @phan-suppress PhanAccessMethodInternal
     */
    public function generate(): void
    {
        if (
            !is_dir(self::TWIG_CACHE_DIR)
            && !mkdir(self::TWIG_CACHE_DIR, 0o770, true)
            && !is_dir(self::TWIG_CACHE_DIR)
        ) {
            throw new RuntimeException(sprintf(
                'Unable to create Twig cache directory: %s',
                self::TWIG_CACHE_DIR,
            ));
        }

        $TwigEnvironment = $this->getTwig(false);
        $tplFs = FsTools::getFs(dirname(__DIR__, 2) . '/src/templates');
        // iterate over all the templates
        $templates = $tplFs
            ->listContents('.')
            ->filter(fn(StorageAttributes $attributes): bool => $attributes->isFile());

        foreach ($templates as $template) {
            // force compilation of the template into cache php file
            $TwigEnvironment->load($template->path());
        }
    }
}
