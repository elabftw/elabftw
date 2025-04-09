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

use Elabftw\Enums\Action;
use Elabftw\Models\Config;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

/**
 * For dev purposes: force the schema to a particular version
 */
#[AsCommand(name: 'dev:forceschema')]
final class ForceSchema extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Directly set a schema number in the general config')
            ->addArgument('schema', InputArgument::REQUIRED, 'Target schema number')
            ->setHelp('This command allows you to directly set the value of the schema number in the config table.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schemaNumber = $input->getArgument('schema');
        $Config = Config::getConfig();
        $Config->patch(Action::Update, array('schema' => $schemaNumber));
        $output->writeln(sprintf('Changing schema to %d', $schemaNumber));
        return Command::SUCCESS;
    }
}
