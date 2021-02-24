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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prepare the database for the 3.4 update
 * Issue: for old databases, the FK fk_users_teams_id will not exist and cause error
 */
class UpdateTo34 extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'db:updateto34';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Update the database for version 3.4.0')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This ensures that the update works for everyone.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Preparing database for 3.4 update',
            '=================================',
        ));
        $Sql = new Sql();
        try {
            $Sql->execFile('prepare34-a.sql');
        } catch (\Exception $e) {
            $output->writeln(array(
                'OK',
            ));
        }
        try {
            $Sql->execFile('prepare34-b.sql');
        } catch (\Exception $e) {
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
        return 0;
    }
}
