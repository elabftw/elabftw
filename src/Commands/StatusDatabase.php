<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

use function array_flip;
use function sprintf;

/**
 * Show migration state.
 */
#[AsCommand(name: 'db:status')]
final class StatusDatabase extends Command
{
    public function __construct(private FilesystemOperator $fs, private readonly bool $useBuildInfo = true)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Show database migration status');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $Migrations = new Migrations($this->fs, $this->useBuildInfo);
        $available = $Migrations->getAvailable();
        if ($available === array()) {
            $output->writeln('No migrations found.');
            return Command::SUCCESS;
        }

        $applied = array_flip($Migrations->getApplied());
        foreach ($available as $migration => $filename) {
            $status = isset($applied[$migration]) ? 'applied' : 'pending';
            $output->writeln(sprintf('%-7s %s %s', $status, $migration, $filename));
        }
        return Command::SUCCESS;
    }
}
