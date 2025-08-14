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

use function dirname;

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
