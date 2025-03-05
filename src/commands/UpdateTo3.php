<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Elabftw\Sql;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

/**
 * Prepare the database for the 3.0 update
 */
#[AsCommand(name: 'db:updateto3')]
final class UpdateTo3 extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Prepare the database for the update to 3.0')
            ->setHelp('This will rename some columns before the rest of the update can be applied.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Preparing database for 3.0 update',
            '=================================',
        ));
        $Sql = new Sql(new Fs(new LocalFilesystemAdapter(dirname(__DIR__) . '/sql')));
        $Sql->execFile('prepare30.sql');
        $output->writeln('Database ready to be cleaned. Now running db:clean command...');

        /** @psalm-suppress PossiblyNullReference */
        $command = $this->getApplication()->find('db:clean');

        $arguments = array(
            'command' => 'db:clean',
        );

        $cmdInput = new ArrayInput($arguments);
        $returnCode = $command->run($cmdInput, $output);
        if ($returnCode === 0) {
            $output->writeln('Now ready to update to latest version. Running db:update command...');
            /** @psalm-suppress PossiblyNullReference */
            $command = $this->getApplication()->find('db:update');

            $arguments = array(
                'command' => 'db:update',
            );

            $cmdInput = new ArrayInput($arguments);
            $command->run($cmdInput, $output);
        }
        return Command::SUCCESS;
    }
}
