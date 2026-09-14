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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

use function ctype_digit;

/**
 * For dev purposes: force the schema to a particular version
 */
#[AsCommand(name: 'dev:forceschema')]
final class ForceSchema extends Command
{
    public function __construct(private FilesystemOperator $fs)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Repair migration history without executing migration SQL')
            ->addArgument('schema', InputArgument::REQUIRED, 'Migration ID, or a legacy schema number')
            ->addOption('forget', null, InputOption::VALUE_NONE, 'Mark the named migration as pending')
            ->setHelp('By default, mark one migration as applied. --forget removes its history record. Neither operation changes the schema. Numeric arguments only repair the legacy baseline.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = (string) $input->getArgument('schema');
        $Migrations = new Migrations($this->fs, $output);
        if (ctype_digit($name)) {
            if ($input->getOption('forget')) {
                throw new ImproperActionException('--forget requires a timestamped migration ID.');
            }
            $Migrations->forceLegacy($name);
            $Config = Config::getConfig();
            $Config->configArr = $Config->readAll();
            $output->writeln('Changing schema to ' . $name . ' (metadata only).');
        } else {
            $Migrations->mark($name, $input->getOption('forget'));
            $output->writeln($name . ($input->getOption('forget') ? ' marked as pending.' : ' marked as applied.') . ' No migration SQL was executed.');
        }
        return Command::SUCCESS;
    }
}
