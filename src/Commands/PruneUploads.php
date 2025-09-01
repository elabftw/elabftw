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

use Elabftw\Services\UploadsPruner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

/**
 * To remove deleted files completely
 */
#[AsCommand(name: 'prune:uploads')]
final class PruneUploads extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Remove deleted uploaded files')
            ->setHelp('Remove uploaded files marked as deleted from the filesystem and from the database');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Pruning uploads',
            '===============',
        ));
        $Cleaner = new UploadsPruner();
        $cleanedNumber = $Cleaner->cleanup();
        $output->writeln(sprintf('Removed %d uploads', $cleanedNumber));
        return Command::SUCCESS;
    }
}
