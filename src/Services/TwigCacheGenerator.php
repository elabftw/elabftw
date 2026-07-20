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
use Symfony\Component\Console\Output\OutputInterface;

use function dirname;
use function is_dir;
use function mkdir;
use function sprintf;
use function count;

/**
 * Generate Twig cache
 */
final class TwigCacheGenerator
{
    use TwigTrait;

    public function __construct(private readonly string $twigCacheDir, private readonly OutputInterface $output) {}

    /**
     * Generate a twig cache file for all the templates in the template dir
     * @phan-suppress PhanAccessMethodInternal
     */
    public function warm(): bool
    {
        if (
            !is_dir($this->twigCacheDir)
            && !mkdir($this->twigCacheDir, 0o770, true)
            && !is_dir($this->twigCacheDir)
        ) {
            throw new RuntimeException(sprintf(
                'Unable to create Twig cache directory: %s',
                $this->twigCacheDir,
            ));
        }

        $tplPath = dirname(__DIR__, 2) . '/src/templates';
        $tplFs = FsTools::getFs($tplPath);

        $this->output->writeln(array(
            sprintf('Generating Twig cache files in: %s', $this->twigCacheDir),
            sprintf('Loading Twig templates from: %s', $tplPath),
        ));

        $TwigEnvironment = $this->getTwig(false);
        // iterate over all the templates
        $templates = $tplFs
            ->listContents('.')
            ->filter(fn(StorageAttributes $attributes): bool => $attributes->isFile())
            ->toArray();

        $this->output->writeln(sprintf('Found %d templates to process.', count($templates)));
        foreach ($templates as $template) {
            $this->output->writeln(sprintf('Processing: %s', $template->path()));
            // force compilation of the template into cache php file
            $TwigEnvironment->load($template->path());
        }
        $this->output->writeln('Success. All the templates are now cached by Twig.');
        return true;
    }
}
