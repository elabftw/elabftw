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

use Elabftw\Exceptions\ImproperActionException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

use function gmdate;
use function preg_match;
use function sprintf;

/**
 * For dev purposes: generate a new empty schema file
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
        $this->setDescription('Generate a new database schema migration file')
            ->addArgument('name', InputArgument::REQUIRED, 'Descriptive snake_case migration name')
            ->setHelp('Generate timestamped up/down SQL files in src/sql/migrations. Example: dev:genschema add_booking_color');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = (string) $input->getArgument('name');
        if (!preg_match('/^[a-z][a-z0-9_]{0,172}$/D', $name)) {
            throw new ImproperActionException('Use a snake_case name starting with a letter (at most 173 characters).');
        }
        $id = gmdate('Y_m_d_His') . '_' . $name;
        $up = 'migrations/' . $id . '.sql';
        $down = 'migrations/' . $id . '-down.sql';
        if ($this->fs->fileExists($up) || $this->fs->fileExists($down)) {
            throw new ImproperActionException('This migration already exists. Choose another name or try again in a second.');
        }
        $this->fs->write($up, sprintf("-- Apply %s\n\n", $id));
        $this->fs->write($down, sprintf("-- Revert %s\n-- Must also handle a partially applied up migration.\n\n", $id));
        $output->writeln('Created file: ' . $up);
        $output->writeln('Created file: ' . $down);
        return Command::SUCCESS;
    }
}
