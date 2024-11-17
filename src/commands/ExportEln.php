<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Interfaces\StorageInterface;
use Elabftw\Make\MakeTeamEln;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZipStream\ZipStream;

/**
 * Export data in .eln format
 */
#[AsCommand(name: 'export:eln')]
class ExportEln extends Command
{
    public function __construct(private StorageInterface $Fs)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Export data in ELN file format')
            ->setHelp('This command allows you to create a .eln file containing data from a whole team. Use verbose flags (-v or -vv) to get more information about what is happening.')
            ->addArgument('teamid', InputArgument::REQUIRED, 'Target team ID')
            ->addOption('users', 'u', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only include these userids', array())
            ->addOption('rcat', 'r', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only include these categories of resources', array());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $teamid = (int) $input->getArgument('teamid');
        $absolutePath = $this->Fs->getPath(sprintf(
            'export-elabftw-%s-team-%d.eln',
            date('Y-m-d_H-i-s'),
            $teamid,
        ));
        $fileStream = fopen($absolutePath, 'wb');
        if ($fileStream === false) {
            throw new RuntimeException('Could not open output stream!');
        }

        $ZipStream = new ZipStream(sendHttpHeaders: false, outputStream: $fileStream);
        $users = array_map('intval', $input->getOption('users'));
        $resourcesCategories = array_map('intval', $input->getOption('rcat'));
        $Maker = new MakeTeamEln($ZipStream, $teamid, $users, $resourcesCategories);
        $Maker->getStreamZip();

        fclose($fileStream);

        $output->writeln(sprintf('Team with id %d successfully exported as ELN archive.', $teamid));
        $output->writeln('Copy the generated archive from the container to the current directory with:');
        $output->writeln(sprintf('docker cp elabftw:%s .', $absolutePath));

        return Command::SUCCESS;
    }
}
