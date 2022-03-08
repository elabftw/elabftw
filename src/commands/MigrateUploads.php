<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Services\StorageFactory;
use Elabftw\Services\UploadsMigrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * To move local filesystem uploads to S3 storage
 */
class MigrateUploads extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'uploads:migrate';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Move uploads to S3 storage')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Upload all local filesystem user uploaded files to S3 bucket storage');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isVerbose()) {
            $output->writeln(array(
                'Migrating uploads',
                '=================',
            ));
        }
        $migrator = new UploadsMigrator(
            (new StorageFactory(StorageFactory::LOCAL))->getStorage()->getFs(),
            (new StorageFactory(StorageFactory::S3))->getStorage()->getFs(),
        );
        $number = $migrator->migrate();
        if ($output->isVerbose()) {
            $output->writeln(sprintf('Moved %d uploads', $number));
        }
        return 0;
    }
}
