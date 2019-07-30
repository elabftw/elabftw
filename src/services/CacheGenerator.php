<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Models\Config;
use Elabftw\Traits\TwigTrait;

/**
 * Generate Twig cache
 */
class CacheGenerator
{
    use TwigTrait;

    /**
     * Generate a twig cache file for all the templates in the template dir
     *
     * @return void
     */
    public function generate(): void
    {
        $TwigEnvironment = $this->getTwig(new Config());
        $tplDir = \dirname(__DIR__, 2) . '/src/templates';
        // iterate over all the templates
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tplDir), \RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            // force compilation
            if ($file->isFile()) {
                $TwigEnvironment->loadTemplate(str_replace($tplDir . '/', '', $file));
            }
        }
    }
}
