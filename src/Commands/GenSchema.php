<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Elabftw\Migrations;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

use function preg_replace;
use function sprintf;
use function strtolower;
use function trim;

/**
 * For dev purposes: generate a new empty migration file
 */
#[AsCommand(name: 'dev:genschema')]
final class GenSchema extends Command
{
    public function __construct(private FilesystemOperator $fs)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Generate a new database migration file')
            ->setHelp('Generate a UUIDv7 migration pair. The optional name is appended to make filenames easier to read.')
            ->addArgument('name', InputArgument::OPTIONAL, 'Short migration description, for example add_step_groups');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $uuid = Migrations::generateUuidV7();
        $slug = $this->slugify((string) $input->getArgument('name'));
        $suffix = $slug === '' ? '' : '_' . $slug;
        $basename = sprintf('migration-%s%s', $uuid, $suffix);

        $output->writeln(sprintf('Generating migration %s', $uuid));
        $filename = $basename . '.sql';
        $this->fs->write($filename, sprintf("-- migration %s\n\n", $uuid));
        $output->writeln('Created file: ' . $filename);

        $filename = $basename . '.down.sql';
        $this->fs->write($filename, sprintf("-- revert migration %s\n\n", $uuid));
        $output->writeln('Created file: ' . $filename);
        return Command::SUCCESS;
    }

    private function slugify(string $name): string
    {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name))) ?? '';
        return trim($slug, '-');
    }
}
