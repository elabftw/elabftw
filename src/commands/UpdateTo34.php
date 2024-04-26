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
use Exception;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prepare the database for the 3.4 update
 * Issue: for old databases, the FK fk_users_teams_id will not exist and cause error
 */
#[AsCommand(name: 'db:updateto34')]
class UpdateTo34 extends Command
{
    protected function configure(): void
    {
        $this
            ->setDescription('Update the database for version 3.4.0')
            ->setHelp('This ensures that the update works for everyone.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Preparing database for 3.4 update',
            '=================================',
        ));
        $Sql = new Sql(new Fs(new LocalFilesystemAdapter(dirname(__DIR__) . '/sql')));
        try {
            $Sql->execFile('prepare34-a.sql');
        } catch (Exception) {
            $output->writeln(array(
                'OK',
            ));
        }
        try {
            $Sql->execFile('prepare34-b.sql');
        } catch (Exception) {
            $output->writeln(array(
                'OK',
            ));
        }

        $output->writeln('Now ready to update to latest version. Running db:update command...');
        /** @psalm-suppress PossiblyNullReference */
        $command = $this->getApplication()->find('db:update');

        $arguments = array(
            'command' => 'db:update',
        );

        $cmdInput = new ArrayInput($arguments);
        $command->run($cmdInput, $output);
        return Command::SUCCESS;
    }
}
