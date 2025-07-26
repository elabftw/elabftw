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

use Elabftw\Enums\Storage;
use Elabftw\Services\UploadsMigrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

/**
 * To move local filesystem uploads to S3 storage
 */
#[AsCommand(name: 'uploads:migrate')]
final class MigrateUploads extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Move uploads to S3 storage')
            ->setHelp('Upload all local filesystem user uploaded files to S3 bucket storage');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Migrating uploads',
            '=================',
        ));
        $migrator = new UploadsMigrator(
            Storage::LOCAL->getStorage()->getFs(),
            Storage::S3->getStorage()->getFs(),
        );
        $number = $migrator->migrate();
        $output->writeln(sprintf('Moved %d uploads', $number));
        $output->writeln('Now fixing links in body...');
        $migrator->fixBodies();
        return Command::SUCCESS;
    }
}
